<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Query\FakeFinalizedOrderStorage;
use MyVendor\BeMart\Be\Reason\Service\FakeSession;
use MyVendor\BeMart\Be\Reason\Service\SessionInterface;
use MyVendor\BeMart\Module\HtmlModule;
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
 * `SessionInterface` is rebound to `customer-001`.
 *
 * EC-CUBE's history.twig renders per-Shipping blocks, PaymentMethod,
 * the order message and MailHistories — entities BeMart's
 * MypageHistoryFetched projection does not carry. Those blocks are
 * omitted and enumerated as missing-body-field residuals; see the port
 * header in var/templates/Page/Mypage/History.html.twig.
 */
final class MypageHistoryHtmlRenderTest extends TestCase
{
    /**
     * EC-CUBE lines with no BeMart counterpart and vice versa. Each entry
     * is a whitespace-collapsed line; the comment states WHY it is
     * acceptable.
     *
     * @var list<string>
     */
    private const RESIDUAL_ALLOWLIST = [
        // --- frame: EC-CUBE-runtime-only <head> nodes (shared) ----------
        '<meta name="eccube-csrf-token" content="">',
        '<script>',
        '$(function() {',
        '$.ajaxSetup({',
        "'headers': {",
        '}',
        '});',
        '});',
        '</script>',
        '<title>BeMart / マイページ</title>',
        '<title>EC-CUBE / マイページ</title>',
        '<meta name="author" content="">',

        // --- order detail: per-Shipping address blocks (missing body) ---
        // EC-CUBE's history.twig renders one `ec-orderDelivery__title` +
        // address block per `Order.Shippings` row (recipient name, postal
        // address, delivery date/time, the shipping provider). BeMart's
        // MypageHistoryFetched projection carries only the order totals +
        // a flat `items` list — no Shipping entities — so the per-shipping
        // title / address / delivery-date rows are omitted. Follow-up:
        // the history projection would need a `shippings` sub-array.
        '<div class="ec-orderDelivery__title">お届け先</div>',
        '<p></p>',
        '<p>&nbsp;&nbsp;',
        '(&nbsp;)</p>',
        '<p>〒 </p>',
        // per-item product THUMBNAIL — EC-CUBE renders an
        // `ec-imageGrid__img` wrapper with the product image; BeMart's
        // OrderItemEntity carries no image, so the thumbnail is omitted.
        '<div class="ec-imageGrid__img">',
        '<img src="/assets/img/common/no_image_product.png"/>',
        // per-shipping delivery-method / date / time definition rows —
        // part of the omitted Shipping entity family.
        '<dt>配送方法 :</dt>',
        '<dd></dd>',
        '<dt>お届け日 :</dt>',
        '<dd>指定なし</dd>',
        '<dt>お届け時間 :</dt>',

        // --- order detail: payment / message / mail blocks (missing) ----
        // EC-CUBE renders `ec-orderPayment` (Order.PaymentMethod),
        // `ec-orderConfirm` (Order.message) and `ec-orderMails`
        // (Order.MailHistories). BeMart's projection carries none of
        // those entities, so the three blocks are omitted. Follow-up:
        // paymentMethod / message / mailHistories would need adding to
        // MypageHistoryFetched.
        '<div class="ec-orderPayment">',
        '<div class="ec-rectHeading">',
        '<h2>お支払い情報</h2>',
        '<p>お支払い方法 : </p>',
        '<div class="ec-orderConfirm">',
        '<h2>お問い合わせ</h2>',
        '<p>記載なし</p>',
        '<div class="ec-orderMails">',
        '<h2>メール配信履歴一覧</h2>',
        '<p class="ec-reportDescription">メール履歴はありません。</p>',
    ];

    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $meta = new Meta('MyVendor\\BeMart', 'html');
        $module = new HtmlModule($meta);
        $session = new FakeSession('customer-001');
        $module->override(new class ($session) extends AbstractModule {
            public function __construct(private readonly FakeSession $session)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(SessionInterface::class)->toInstance($this->session);
            }
        });
        $injector = new Injector($module, dirname(__DIR__, 2) . '/var/tmp/html');
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    public function testHistoryRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/mypage/history', [
            'orderNo' => FakeFinalizedOrderStorage::SEED_ORDER_NO,
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
            'orderNo' => FakeFinalizedOrderStorage::SEED_ORDER_NO,
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
    public function testHistoryHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $beMart = $this->resource->get('page://self/mypage/history', [
            'orderNo' => FakeFinalizedOrderStorage::SEED_ORDER_NO,
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

        // The residual is larger than the other Mypage pages because
        // MypageHistoryFetched is a deliberately-thin projection: it
        // carries the order totals + a flat items list but NOT the
        // Shipping / PaymentMethod / order-message / MailHistory
        // entities EC-CUBE's history.twig renders. Those four omitted
        // blocks account for ~40 enumerated residual lines — every one
        // justified in RESIDUAL_ALLOWLIST / isResidual and flagged as a
        // follow-up (the projection would need shipping/payment/mail
        // sub-objects). The guard catches genuine port drift only.
        $this->assertLessThanOrEqual(
            45,
            count($onlyInEcCube) + count($onlyInBeMart),
            'residual diff unexpectedly large — port may have drifted',
        );
    }

    private static function isResidual(string $line): bool
    {
        if (in_array($line, self::RESIDUAL_ALLOWLIST, true)) {
            return true;
        }

        foreach ([
            'eccube-csrf-token',
            '<title>',
            'meta name="author"',
            // The order-detail blocks below carry data BeMart's
            // projection does not model (Shippings, PaymentMethod,
            // MailHistories). Their inner lines vary, so match by family.
            'ec-orderDelivery__address',
            'ec-definitions--soft',
            'ec-orderMail',
            'ec-rectHeading',
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

        // The same logical order as BeMart's FakeFinalizedOrderStorage
        // seed (SEED_ORDER_NO): subtotal 11000 / delivery 600 / tax 1100
        // / total 12700 / addPoint 127, two product items
        // (`サンプル商品 A` x1 @￥1,200, `Sample Product B` x1 @￥9,800).
        // EC-CUBE nests the item rows under `Order.Shippings`; BeMart's
        // projection has a FLAT `items` list (no Shipping entity), so the
        // port renders the items directly under `ec-orderDelivery`. To
        // keep the item rows comparable, EC-CUBE is fed a SINGLE Shipping
        // carrying the same two items — the item-content markup then
        // diffs to zero; the per-shipping ADDRESS / title / delivery-date
        // rows + the `ec-imageGrid__img` thumbnail wrapper remain the
        // enumerated missing-body-field residual.
        $items = [
            new EcCubeStub([
                'productName' => 'サンプル商品 A',
                'quantity' => 1,
                'price_inc_tax' => 1200,
                'Product' => null,
                'ProductClass' => null,
                'productClass' => null,
            ]),
            new EcCubeStub([
                'productName' => 'Sample Product B',
                'quantity' => 1,
                'price_inc_tax' => 9800,
                'Product' => null,
                'ProductClass' => null,
                'productClass' => null,
            ]),
        ];
        $shipping = new EcCubeStub([
            'productOrderItems' => $items,
            'name01' => '', 'name02' => '', 'kana01' => '', 'kana02' => '',
            'postal_code' => '', 'Pref' => '', 'addr01' => '', 'addr02' => '',
            'phone_number' => '',
            'shipping_delivery_name' => '',
            'shipping_delivery_date' => null,
            'shipping_delivery_time' => null,
        ]);
        $order = new EcCubeStub([
            'order_date' => '2026-04-01 10:00:00',
            'order_no' => FakeFinalizedOrderStorage::SEED_ORDER_NO,
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
            'PaymentMethod' => '',
            'message' => null,
            'MailHistories' => [],
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
                'Head' => null, 'BodyAfter' => null, 'Header' => [0 => 'x'],
                'ContentsTop' => null, 'SideLeft' => null, 'SideRight' => null,
                'MainTop' => null, 'MainBottom' => null, 'ContentsBottom' => null,
                'Footer' => [0 => 'x'], 'Drawer' => [0 => 'x'], 'CloseBodyBefore' => null,
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
        $twig->addFilter(new TwigFilter('nl2br', static fn (string $s): string => nl2br((string) $s)));
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
        $twig->addFunction(new TwigFunction('asset', static fn (string $p): string => '/' . $p));
        $twig->addFunction(new TwigFunction('url', static function (string $r, array $p = []): string {
            return '/' . $r . ($p ? '?' . http_build_query($p) : '');
        }));
        $twig->addFunction(new TwigFunction('path', static function (string $r, array $p = []): string {
            return '/' . $r . ($p ? '?' . http_build_query($p) : '');
        }));
        $twig->addFunction(new TwigFunction('csrf_token', static fn (): string => ''));
        $twig->addFunction(new TwigFunction('csrf_token_for_anchor', static fn (): string => ''));
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
