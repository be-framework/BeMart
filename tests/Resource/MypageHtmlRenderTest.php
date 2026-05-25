<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Entity\FinalizedOrderEntity;
use MyVendor\BeMart\Be\Reason\Entity\OrderItemEntity;
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
 * Phase 3 — fidelity check for the Mypage dashboard (goMypage) HTML port.
 *
 * Same standard as {@see CartHtmlRenderTest}: BeMart's storefront
 * templates are PORTS of EC-CUBE 4.3's default-theme Twig.
 *
 * `Mypage/index.twig` is a DATA page. The Mypage resource requires AUTHN
 * (the Be Final raises UnauthenticatedException on a null / unknown
 * session), so this test rebinds `SessionInterface` to a real fixture
 * customer (alice) in the `html` context before rendering.
 *
 * The dashboard surfaces the recent-orders SUMMARY. Phase 3 enrichment
 * — each recentOrders row now carries an `items` sub-array (read via
 * OrderQuery::itemsByOrderNo), so the per-order `ec-historyRole__detail`
 * line-item block is wired and diffs to zero against EC-CUBE. The only
 * residual left is the EC-CUBE <head> furniture + the paged-history
 * pager wrapper (BeMart's dashboard is not a paged view). See the port
 * header in var/templates/Page/Mypage.html.twig.
 */
final class MypageHtmlRenderTest extends TestCase
{
    private const ALICE_ID = '0123456789abcdef0123456789abcdef';

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

        // --- dashboard: pager (not a paged view in BeMart) --------------
        // EC-CUBE's index.twig is a paged history view — it wraps a
        // `pager.twig` include in `<div class="ec-pagerRole">`. BeMart's
        // Mypage dashboard surfaces only the recent-orders SUMMARY (a
        // fixed-size list, not paged), so the pager wrapper + include are
        // omitted. EC-CUBE-runtime only.
        '<div class="ec-pagerRole">',
    ];

    /** Fixed alice order used identically on both sides of the diff. */
    private const ALICE_ORDER_NO = 'alice0000000000000000000000000001';
    private const ALICE_ORDER_DATE = '2026-05-01 12:00:00';

    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $meta = new Meta('MyVendor\\BeMart', 'html');
        $module = new HtmlModule($meta);
        $session = new FakeSession(self::ALICE_ID);
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

        // Seed a recent order for alice so the dashboard has a row to
        // render — fed identically to the EC-CUBE side below (mirrors
        // MypageResourceTest::testOnGetIncludesRecentOrders).
        $storage = $injector->getInstance(FakeFinalizedOrderStorage::class);
        $storage->put(new FinalizedOrderEntity(
            orderNo: self::ALICE_ORDER_NO,
            preOrderId: 'alice00000000000000000000000000000000pre',
            customerId: self::ALICE_ID,
            paymentMethodId: 2,
            subtotal: 3000,
            deliveryFeeTotal: 500,
            charge: 0,
            discount: 0,
            tax: 300,
            total: 3800,
            paymentTotal: 3800,
            addPoint: 38,
            usePoint: 0,
            orderStatus: FinalizedOrderEntity::STATUS_NEW,
            orderDate: self::ALICE_ORDER_DATE,
            paymentDate: self::ALICE_ORDER_DATE,
        ));
        // Phase 3 enrichment — the dashboard's recentOrders rows carry an
        // `items` sub-array; seed two line items so the per-order
        // `ec-historyRole__detail` block has content, fed identically to
        // the EC-CUBE side below.
        $storage->putItems(self::ALICE_ORDER_NO, [
            new OrderItemEntity(
                orderNo: self::ALICE_ORDER_NO,
                productCode: 'sample-001',
                productName: 'サンプル商品 A',
                quantity: 2,
                unitPrice: 1200,
            ),
            new OrderItemEntity(
                orderNo: self::ALICE_ORDER_NO,
                productCode: 'sample-002',
                productName: 'Sample Product B',
                quantity: 1,
                unitPrice: 600,
            ),
        ]);

        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    public function testMypageRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/mypage');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('<div class="ec-layoutRole">', $html);
        $this->assertStringContainsString('</body>', $html);

        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    public function testMypagePreservesEcCubeMarkupStructure(): void
    {
        $html = $this->resource->get('page://self/mypage')->toString();

        foreach ([
            '<div class="ec-mypageRole">',
            '<div class="ec-pageHeader">',
            '<div class="ec-navlistRole">',
            'class="ec-navlistRole__navlist"',
            '<div class="ec-welcomeMsg">',
        ] as $needle) {
            $this->assertStringContainsString($needle, $html, "ported markup missing: {$needle}");
        }
    }

    /**
     * The honesty test: diff BeMart's rendered dashboard against
     * EC-CUBE's own rendering. Every difference must be in the allowlist.
     */
    public function testMypageHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $beMart = $this->resource->get('page://self/mypage')->toString();
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
            "BeMart's mypage HTML diverged from EC-CUBE's beyond the "
            . "residual allowlist. Unexplained diff lines:\n  "
            . implode("\n  ", $unexplained)
            . "\n\n(only-in-EC-CUBE: " . count($onlyInEcCube)
            . ', only-in-BeMart: ' . count($onlyInBeMart) . ')',
        );

        // Phase 3 enrichment shrank the residual: the per-order
        // line-item detail block is now wired (the recentOrders `items`
        // sub-array) and diffs to zero. The remaining ~12 lines are the
        // EC-CUBE <head> furniture + the ec-pagerRole node (BeMart's
        // dashboard is not a paged view).
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

        // One recent order with two line items — fed identically to
        // BeMart's seed (Phase 3 enrichment: the recentOrders projection
        // now carries an `items` sub-array). The order-status / date
        // values are fed identically so the dashboard rows diff to zero
        // (BeMart projects orderStatus as the integer master id; the stub
        // feeds that same integer). `Product` is null on each item so
        // EC-CUBE's no-image-placeholder branch fires — matching BeMart's
        // dashboard item projection, which carries no Product entity.
        $items = [
            new EcCubeStub([
                'product_name' => 'サンプル商品 A',
                'class_category_name1' => '',
                'class_category_name2' => '',
                'price_inc_tax' => 1200,
                'quantity' => 2,
                'Product' => null,
            ]),
            new EcCubeStub([
                'product_name' => 'Sample Product B',
                'class_category_name1' => '',
                'class_category_name2' => '',
                'price_inc_tax' => 600,
                'quantity' => 1,
                'Product' => null,
            ]),
        ];
        $order = new EcCubeStub([
            'order_date' => self::ALICE_ORDER_DATE,
            'order_no' => self::ALICE_ORDER_NO,
            'CustomerOrderStatus' => FinalizedOrderEntity::STATUS_NEW,
            'MergedProductOrderItems' => $items,
        ]);

        return $twig->render('Mypage/index.twig', [
            'pagination' => new EcCubeStub(
                ['totalItemCount' => 1, 'paginationData' => []],
                [$order],
            ),
            'BaseInfo' => new EcCubeStub([
                'shop_name' => 'EC-CUBE',
                'option_favorite_product' => true,
                'option_point' => false,
                'option_mypage_order_status_display' => true,
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
                'request' => new EcCubeStub(['_route' => 'mypage']),
                'user' => new EcCubeStub(['name01' => '山田', 'name02' => 'アリス', 'point' => 0]),
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
        $twig->addFilter(new TwigFilter('nl2br', static fn (string $s): string => nl2br($s)));
        $twig->addFilter(new TwigFilter('number_format', static fn ($n): string => number_format((float) $n)));
        $twig->addFilter(new TwigFilter('date_sec', static fn ($d): string => (string) $d));
        $twig->addFilter(new TwigFilter('price', static function ($n): string {
            $f = new \NumberFormatter('ja_JP', \NumberFormatter::CURRENCY);

            return (string) $f->formatCurrency((float) ($n ?? 0), 'JPY');
        }));
        $twig->addFilter(new TwigFilter('no_image_product', static fn ($s): string => $s ? (string) $s : 'assets/img/common/no_image_product.png'));

        $twig->addFunction(new TwigFunction('trans', $trans));
        $twig->addFunction(new TwigFunction('is_granted', static fn (): bool => false));
        EcCubeAssetStub::register($twig);
        EcCubeRouteStub::register($twig);
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
