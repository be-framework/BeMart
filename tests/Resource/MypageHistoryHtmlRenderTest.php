<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeSession;
use MyVendor\BeMart\Be\Reason\Service\CustomerSession;
use MyVendor\BeMart\Tests\Support\HtmlTestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;
use Twig\Environment;
use Twig\TwigFilter;
use Twig\TwigFunction;

use function array_diff;
use function array_filter;
use function array_values;
use function count;
use function dirname;
use function explode;
use function http_build_query;
use function implode;
use function in_array;
use function is_dir;
use function preg_replace;
use function str_contains;
use function trim;

/**
 * Phase 3 — fidelity check for the order-history detail (goMypageHistory)
 * HTML port.
 *
 * Same standard as {@see CartHtmlRenderTest}: BeMart's storefront
 * templates are PORTS of EC-CUBE 4.3's default-theme Twig.
 *
 * `Mypage/history.twig` is a DATA page. The History resource requires
 * AUTHN + AUTHZ (the order's owner must match the session); the seed
 * order belongs to `customer-001`, so the `html` context's
 * `CustomerSession` is rebound to `customer-001`.
 *
 * Phase 3 enrichment — the MypageHistoryFetched projection was widened
 * to carry the per-shipping address blocks (with their line items), the
 * payment method, the order message and the mail-delivery history. The
 * History template now wires all four, so EC-CUBE's history.twig and
 * BeMart's port diff only on the EC-CUBE runtime's own <head> furniture
 * (the residual allowlist). See the port header in
 * var/templates/Page/Mypage/History.html.twig.
 */
final class MypageHistoryHtmlRenderTest extends TestCase
{
    /**
     * EC-CUBE lines with no BeMart counterpart and vice versa. Each entry
     * is a whitespace-collapsed line; the comment states WHY it is
     * acceptable.
     *
     * Phase 3 ENRICHMENT — this allowlist shrank from ~40 lines to the
     * ~11-line EC-CUBE-runtime-only baseline. The MypageHistoryFetched
     * projection was widened to carry the per-shipping address blocks,
     * the payment method, the order message and the mail-delivery
     * history; the History template now wires all four. Every former
     * "missing body field" residual (the omitted shipping / payment /
     * message / mail blocks) is gone — the only residual left is the
     * EC-CUBE runtime's own <head> furniture, shared with every other
     * DATA-page render-diff test.
     *
     * @var list<string>
     */
    private const RESIDUAL_ALLOWLIST = [
        // --- frame: EC-CUBE-runtime-only <head> nodes (shared) ----------
        // EC-CUBE's default_frame.twig emits a CSRF <meta> + an inline
        // jQuery $.ajaxSetup() <script> that wires the token into every
        // XHR. BeMart's storefront is server-rendered hypermedia — there
        // is no global XHR CSRF wiring — so these nodes have no port
        // counterpart. Same baseline residual as CartHtmlRenderTest et al.
        '<meta name="eccube-csrf-token" content="">',
        '<script>',
        '$(function() {',
        '$.ajaxSetup({',
        "'headers': {",
        '}',
        '});',
        '});',
        '</script>',
        // The shop name in the <title> differs by brand (BeMart vs
        // EC-CUBE) — a fixture/runtime label, not port drift.
        '<title>BeMart / マイページ</title>',
        '<title>EC-CUBE / マイページ</title>',
        '<meta name="author" content="">',
    ];

    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $meta = new Meta('MyVendor\\BeMart', 'html');
        $module = new HtmlTestModule($meta);
        $session = new FakeSession('customer-001');
        $module->override(new class ($session) extends AbstractModule {
            public function __construct(private readonly FakeSession $session)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(CustomerSession::class)->toInstance($this->session);
            }
        });
        $injector = new Injector($module, dirname(__DIR__, 2) . '/var/tmp/html');
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    public function testHistoryRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/mypage/history', [
            'orderNo' => 'past0000000000000000000000000001',
        ]);

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('<div class="ec-layoutRole">', $html);
        $this->assertStringContainsString('</body>', $html);

        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    public function testHistoryPreservesEcCubeMarkupStructure(): void
    {
        $html = $this->resource->get('page://self/mypage/history', [
            'orderNo' => 'past0000000000000000000000000001',
        ])->toString();

        foreach ([
            '<div class="ec-orderRole">',
            '<div class="ec-orderRole__detail">',
            '<div class="ec-orderOrder">',
            '<div class="ec-orderRole__summary">',
            '<div class="ec-totalBox">',
        ] as $needle) {
            $this->assertStringContainsString($needle, $html, "ported markup missing: {$needle}");
        }
    }

    /**
     * The honesty test: diff BeMart's rendered history against EC-CUBE's
     * own rendering of the same logical order. Every difference must be
     * in the residual allowlist.
     */
    #[\PHPUnit\Framework\Attributes\Group('ec-cube-reference')]
    public function testHistoryHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $beMart = $this->resource->get('page://self/mypage/history', [
            'orderNo' => 'past0000000000000000000000000001',
        ])->toString();
        $ecCube = $this->renderEcCube();

        $beMartLines = $this->normalize($beMart);
        $ecCubeLines = $this->normalize($ecCube);

        $onlyInEcCube = array_values(array_diff($ecCubeLines, $beMartLines));
        $onlyInBeMart = array_values(array_diff($beMartLines, $ecCubeLines));

        $unexplained = array_values(array_filter(
            [...$onlyInEcCube, ...$onlyInBeMart],
            static fn (string $line): bool => ! self::isResidual($line),
        ));

        $this->assertSame(
            [],
            $unexplained,
            "BeMart's history HTML diverged from EC-CUBE's beyond the "
            . "residual allowlist. Unexplained diff lines:\n  "
            . implode("\n  ", $unexplained)
            . "\n\n(only-in-EC-CUBE: " . count($onlyInEcCube)
            . ', only-in-BeMart: ' . count($onlyInBeMart) . ')',
        );

        // Phase 3 enrichment shrank the residual from ~40 lines to the
        // EC-CUBE-runtime-only baseline: the MypageHistoryFetched
        // projection now carries the per-shipping address blocks, the
        // payment method, the order message and the mail-delivery
        // history, and the History template wires all four. The only
        // residual left is EC-CUBE's <head> furniture (the CSRF <meta>
        // + ajaxSetup <script>, the brand <title>) — the same baseline
        // every DATA-page render-diff test carries. The guard catches
        // genuine port drift only.
        $this->assertLessThanOrEqual(
            13,
            count($onlyInEcCube) + count($onlyInBeMart),
            'residual diff unexpectedly large — port may have drifted',
        );
    }

    private static function isResidual(string $line): bool
    {
        if (in_array($line, self::RESIDUAL_ALLOWLIST, true)) {
            return true;
        }

        // Only the EC-CUBE-runtime <head> furniture remains a family
        // match — the order-detail blocks (Shippings, PaymentMethod,
        // order message, MailHistories) are now fully ported and diff to
        // zero, so they are no longer residual families.
        foreach ([
            'eccube-csrf-token',
            '<title>',
            'meta name="author"',
        ] as $family) {
            if (str_contains($line, $family)) {
                return true;
            }
        }

        return false;
    }

    private function renderEcCube(): string
    {
        $ecCubeTemplates = dirname(__DIR__, 2)
            . '/tools/ec-cube-source/src/Eccube/Resource/template/default';
        if (! is_dir($ecCubeTemplates)) {
            $this->markTestSkipped('EC-CUBE 4.3 reference clone not present.');
        }

        $twig = new Environment(new EcCubeStubLoader($ecCubeTemplates), [
            'autoescape' => 'html',
            'strict_variables' => false,
        ]);
        $this->registerEcCubeStubs($twig);

        // The same logical order as BeMart's Ray.FakeQuery fixture JSON
        // seed (SEED_ORDER_NO): subtotal 11000 / delivery 600 / tax 1100
        // / total 12700 / addPoint 127, two product items
        // (`サンプル商品 A` x1 @￥1,200, `Sample Product B` x1 @￥9,800).
        //
        // Phase 3 enrichment — the History projection now carries the
        // full per-shipping address blocks, the payment method, the
        // order message and the mail-delivery history. EC-CUBE is fed
        // the SAME enriched seed (one Shipping with a recipient address
        // + delivery method/date/time carrying both items, the `銀行振込`
        // payment method, the `配送は平日希望です。` order message and a
        // single mail-history row) so the previously-omitted shipping /
        // payment / message / mail blocks now diff to zero. Only the
        // EC-CUBE-runtime <head> nodes remain in the residual.
        $items = [
            new EcCubeStub([
                'productName' => 'サンプル商品 A',
                'quantity' => 1,
                'price_inc_tax' => 1200,
                'product' => null,
                'Product' => null,
                'ProductClass' => null,
                'productClass' => null,
            ]),
            new EcCubeStub([
                'productName' => 'Sample Product B',
                'quantity' => 1,
                'price_inc_tax' => 9800,
                'product' => null,
                'Product' => null,
                'ProductClass' => null,
                'productClass' => null,
            ]),
        ];
        $shipping = new EcCubeStub([
            'productOrderItems' => $items,
            'name01' => '山田', 'name02' => '太郎',
            'kana01' => 'ヤマダ', 'kana02' => 'タロウ',
            'postal_code' => '530-0001', 'Pref' => '大阪府',
            'addr01' => '大阪市北区梅田', 'addr02' => '1-2-3',
            'phone_number' => '0612345678',
            'shipping_delivery_name' => 'サンプル宅配便',
            'shipping_delivery_date' => '2026-04-03',
            'shipping_delivery_time' => '午前中',
        ]);
        $mailHistory = new EcCubeStub([
            'send_date' => '2026-04-01 10:05:00',
            'mail_subject' => 'ご注文ありがとうございます',
            'mail_body' => "この度はご注文いただきありがとうございます。\n商品の発送まで今しばらくお待ちください。",
        ]);
        $order = new EcCubeStub([
            'order_date' => '2026-04-01 10:00:00',
            'order_no' => 'past0000000000000000000000000001',
            'CustomerOrderStatus' => 1,
            'usePoint' => 0,
            'addPoint' => 127,
            'subtotal' => 11000,
            'charge' => 0,
            'delivery_fee_total' => 600,
            'taxable_discount' => 0,
            'taxable_total' => 12700,
            'payment_total' => 12700,
            'multiple' => false,
            'Shippings' => [$shipping],
            'tax_free_discount_items' => [],
            'total_by_tax_rate' => [],
            'tax_by_tax_rate' => [],
            'PaymentMethod' => '銀行振込',
            'message' => '配送は平日希望です。',
            'MailHistories' => [$mailHistory],
        ], []);

        return $twig->render('Mypage/history.twig', [
            'Order' => $order,
            'stockOrder' => false,
            'BaseInfo' => new EcCubeStub([
                'shop_name' => 'EC-CUBE',
                'option_favorite_product' => true,
                'option_point' => false,
                'option_mypage_order_status_display' => true,
                'isOptionPoint' => true,
            ]),
            'eccube_config' => ['locale' => 'ja'],
            'Page' => new EcCubeStub([
                'meta_tags' => '',
                'description' => '',
                'author' => '',
                'keyword' => '',
                'meta_robots' => '',
            ]),
            'Layout' => new EcCubeStub([
                'Head' => null, 'BodyAfter' => null, 'Header' => [new EcCubeStub(['file_name' => 'logo'])],
                'ContentsTop' => null, 'SideLeft' => null, 'SideRight' => null,
                'MainTop' => null, 'MainBottom' => null, 'ContentsBottom' => null,
                'Footer' => [new EcCubeStub(['file_name' => 'footer'])], 'Drawer' => [0 => 'x'], 'CloseBodyBefore' => null,
                'ColumnNum' => 1,
            ]),
            'app' => new EcCubeStub([
                'session' => new EcCubeStub([
                    'flashbag' => new EcCubeFlashBag(),
                    'flashBag' => new EcCubeFlashBag(),
                ]),
                'request' => new EcCubeStub(['_route' => 'mypage_history']),
                'user' => new EcCubeStub(['name01' => '', 'name02' => '', 'point' => 0]),
            ]),
            'subtitle' => 'マイページ',
            'title' => 'マイページ',
        ]);
    }

    private function registerEcCubeStubs(Environment $twig): void
    {
        $trans = static function (string $key, array $params = []): string {
            $messages = EcCubeStub::jaMessages();
            $text = $messages[$key] ?? $key;
            foreach ($params as $name => $value) {
                $text = str_replace($name, (string) $value, $text);
            }

            return $text;
        };
        $twig->addFilter(new TwigFilter('trans', $trans));
        // EC-CUBE's real `nl2br` filter is registered `is_safe => html`
        // (it emits literal <br /> tags that must NOT be re-escaped).
        // Mirror that here so the stubbed render matches EC-CUBE's actual
        // behaviour — and BeMart's native Twig `nl2br`.
        $twig->addFilter(new TwigFilter(
            'nl2br',
            static fn (string $s): string => nl2br((string) $s),
            ['is_safe' => ['html']],
        ));
        $twig->addFilter(new TwigFilter('number_format', static fn ($n): string => number_format((float) $n)));
        $twig->addFilter(new TwigFilter('date_sec', static fn ($d): string => (string) $d));
        $twig->addFilter(new TwigFilter('date_day_with_weekday', static fn ($d): string => (string) $d));
        $twig->addFilter(new TwigFilter('price', static function ($n): string {
            $f = new \NumberFormatter('ja_JP', \NumberFormatter::CURRENCY);

            return (string) $f->formatCurrency((float) ($n ?? 0), 'JPY');
        }));
        $twig->addFilter(new TwigFilter('no_image_product', static fn ($s): string => $s ? (string) $s : 'assets/img/common/no_image_product.png'));

        $twig->addFunction(new TwigFunction('trans', $trans));
        $twig->addFunction(new TwigFunction('is_granted', static fn (): bool => false));
        $twig->addFunction(new TwigFunction('is_reduced_tax_rate', static fn (...$a): bool => false));
        EcCubeAssetStub::register($twig);
        EcCubeRouteStub::register($twig);
        $twig->addFunction(new TwigFunction('csrfcsrfToken', static fn (): string => ''));
        $twig->addFunction(new TwigFunction('csrfcsrfToken_for_anchor', static fn (): string => ''));
        $twig->addFunction(new TwigFunction('constant', static fn (string $n): string => $n));
        $twig->addFunction(new TwigFunction('template_from_string', static fn (string $s): string => $s));
    }

    /**
     * @return list<string>
     */
    private function normalize(string $html): array
    {
        $collapsed = (string) preg_replace('/[ \t]+/', ' ', $html);
        $lines = [];
        foreach (explode("\n", $collapsed) as $line) {
            $line = trim($line);
            if ($line !== '') {
                $lines[] = $line;
            }
        }

        return $lines;
    }
}
