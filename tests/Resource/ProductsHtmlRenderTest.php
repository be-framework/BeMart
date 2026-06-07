<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Module\HtmlTestModule;
use PHPUnit\Framework\TestCase;
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
use function is_dir;
use function is_string;
use function preg_replace;
use function str_contains;
use function trim;

/**
 * Phase 3 — fidelity check for the ProductList (goProductList) HTML
 * port.
 *
 * Same standard as {@see CartHtmlRenderTest}: BeMart's storefront
 * templates are PORTS of EC-CUBE 4.3's default-theme Twig.
 *
 * SCOPE — the Products resource is now backed by the storefront catalog
 * query (`GetStorefrontProductListInput` → `StorefrontProductListFetched`):
 * it projects every STATUS_VISIBLE product as `{id, name, price02}`. So
 * this test renders the POPULATED `ec-shelfGrid` branch of EC-CUBE's
 * `Product/list.twig`, feeding BOTH sides the same three visible
 * products the Fake corpus carries (`sample-001`, `sample-002`,
 * `admin-active-001`).
 *
 * The BeMart catalog row is a deliberately SIMPLIFIED port (厳密移植
 * Grade-C, parity with the Cart row): bare name + a single `price02` +
 * the `product_detail` link + the placeholder thumbnail. EC-CUBE's row
 * additionally renders a per-item add-cart `<form>` (ProductClass
 * selects + quantity), an `.ec-modal` add-cart dialog, the
 * `disp_number` / `orderby` sort controls and an `.ec-pagerRole` pager —
 * all needing data the resource body does not carry (a ProductClass
 * join, a Symfony FormView, pagination metadata). Those EC-CUBE-only
 * features are the enumerated residuals in {@see RESIDUAL_ALLOWLIST} /
 * {@see isResidual()}; category / keyword filtering + pagination are
 * deferred to Phase 2.
 */
final class ProductsHtmlRenderTest extends TestCase
{
    /**
     * EC-CUBE lines with no BeMart counterpart and vice versa. Each entry
     * is a whitespace-collapsed line; the comment states WHY it is
     * acceptable.
     *
     * @var list<string>
     */
    private const RESIDUAL_ALLOWLIST = [
        // --- frame: EC-CUBE-runtime-only <head> nodes -------------------
        // EC-CUBE's default_frame.twig wires a live CSRF token into a
        // jQuery `$.ajaxSetup` default header. BeMart's html context has
        // no per-request CSRF widget, so the ported base.html.twig omits
        // the meta and the wiring script. EC-CUBE-runtime only.
        '<meta name="eccube-csrf-token" content="">',
        '$.ajaxSetup({',
        "'headers': {",
        // EC-CUBE's <title> is "<shop_name> / <page title>"; BeMart's
        // html context has no BaseInfo, so the shop name differs.
        '<title>BeMart / 商品一覧</title>',
        '<title>EC-CUBE / 商品一覧</title>',
        '<meta name="author" content="">',

        // --- catalog row: simplified-port omissions (Grade-C) -----------
        // EC-CUBE's `list.twig` add-cart <form> closes with a bare
        // </button> and the 「カートに入れる」 label; BeMart's storefront
        // grid carries no per-item add-cart UI (see the families in
        // isResidual()), so these two trailing lines have no counterpart.
        '</button>',
        'カートに入れる',
        // The add-cart modal's inner role wrapper — `ec-modal` /
        // `ec-inlineBtn` cover the rest of the dialog (see isResidual()).
        '<div class="ec-role">',
    ];

    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $meta = new Meta('MyVendor\\BeMart', 'html');
        $injector = new Injector(
            new HtmlTestModule($meta),
            dirname(__DIR__, 2) . '/var/tmp/html',
        );
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    public function testProductListPageRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/products');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('<div class="ec-layoutRole">', $html);
        $this->assertStringContainsString('</body>', $html);

        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    public function testProductListPagePreservesEcCubeMarkupStructure(): void
    {
        $html = $this->resource->get('page://self/products')->toString();

        foreach ([
            'class="product_page"',
            '<div class="ec-searchnavRole">',
            '<form name="form1" id="form1" method="get" action="?">',
            'class="ec-searchnavRole__topicpath"',
            '<ol class="ec-topicpath">',
            'class="ec-topicpath__item"',
            'class="ec-searchnavRole__infos"',
            'class="ec-searchnavRole__counter"',
            // Populated branch — the storefront catalog query projects
            // five STATUS_VISIBLE products.
            'の商品が見つかりました',
            '<div class="ec-shelfRole">',
            '<ul class="ec-shelfGrid">',
            '<li class="ec-shelfGrid__item">',
            'class="ec-shelfGrid__item-image"',
            'class="price02-default"',
        ] as $needle) {
            $this->assertStringContainsString($needle, $html, "ported markup missing: {$needle}");
        }
    }

    /**
     * The honesty test: diff BeMart's rendered product-list page against
     * EC-CUBE's own rendering of the same (empty) result set. Every
     * difference must be in the residual allowlist.
     */
    #[\PHPUnit\Framework\Attributes\Group('ec-cube-reference')]
    public function testProductListHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $beMart = $this->resource->get('page://self/products')->toString();
        $ecCube = $this->renderEcCubeProductList();

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
            "BeMart's product-list HTML diverged from EC-CUBE's beyond "
            . "the residual allowlist. Unexplained diff lines:\n  "
            . implode("\n  ", $unexplained)
            . "\n\n(only-in-EC-CUBE: " . count($onlyInEcCube)
            . ', only-in-BeMart: ' . count($onlyInBeMart) . ')',
        );

        // Populated branch: the residual is the EC-CUBE-runtime <head>
        // material plus the Grade-C catalog-row omissions (the per-item
        // add-cart <form>, the add-cart modal, the sort controls and the
        // pager — see isResidual()). The add-cart <form> repeats once per
        // visible product, so the line count scales with the five-row
        // fixture; if it balloons past this the port has drifted.
        $this->assertLessThan(
            80,
            count($onlyInEcCube) + count($onlyInBeMart),
            'residual diff unexpectedly large — port may have drifted',
        );
    }

    private static function isResidual(string $line): bool
    {
        foreach (self::RESIDUAL_ALLOWLIST as $allowed) {
            if ($line === $allowed) {
                return true;
            }
        }

        foreach ([
            // --- frame: EC-CUBE-runtime-only artefacts ------------------
            'eccube-csrf-token',                     // <head> CSRF meta
            '<title>',                               // shop-title composition
            'meta name="author"',                    // meta.twig include

            // --- catalog row: simplified-port omissions (Grade-C) -------
            // EC-CUBE's `list.twig` row carries a per-item add-cart UI,
            // an add-cart modal, sort controls and a pager. BeMart's
            // storefront grid is a Grade-C port — name + single price02 +
            // product-detail link only — so the resource body carries no
            // ProductClass join, no Symfony FormView and no pagination
            // metadata. Each family below is one EC-CUBE-only feature.
            'ec-searchnavRole__actions',             // disp_number / orderby wrapper
            'ec-select',                             // sort-control <select> wrapper
            'id="category_id" name="category_id"',    // ported hidden search state
            'id="name" name="name"',                  // ported hidden search state
            'id="pageno" name="pageno"',              // ported hidden search state
            'id="disp_number" name="disp_number"',    // ported hidden search state
            'id="orderby" name="orderby"',            // ported hidden search state
            'disp-number',                            // ported display-count select
            'order-by',                               // ported sort-order select
            '<option value=',                         // ported select options
            'ec-shelfGrid__item-category',            // BeMart catalog enrichment
            'ec-shelfGrid__item-tags',                // BeMart catalog enrichment
            '[form_widget:',                         // stubbed Symfony FormView widget
            'productForm',                           // per-item add-cart <form> + button
            'ec-productRole',                        // add-cart form actions / button wrappers
            'ec-numberInput',                        // add-cart quantity input
            'ec-modal',                              // add-cart completion modal
            'ec-inlineBtn',                          // modal continue / go-to-cart buttons
            'ec-pagerRole',                          // pagination pager
        ] as $family) {
            if (str_contains($line, $family)) {
                return true;
            }
        }

        return false;
    }

    /**
     * The same five STATUS_VISIBLE products the storefront catalog query
     * ({@see \MyVendor\BeMart\Be\Final\StorefrontProductListFetched})
     * projects from the Fake corpus (`be/var/fake/products.json`):
     * `sample-001`, `sample-002`, `admin-active-001`, `api-persist-20260522-001`, and `ui-create-20260522-001`. Each carries the
     * `{id, name, price02}` projection BeMart's resource body exposes;
     * EC-CUBE's `list.twig` reads `getPrice02IncTaxMin` for the
     * single-price (non-ProductClass) row, so it is set to the same
     * `price02` value the port renders.
     *
     * @return list<EcCubeStub>
     */
    private static function visibleProductRows(): array
    {
        $rows = [
            ['id' => 'sample-001', 'name' => 'サンプル商品 A', 'price02' => 1200, 'image' => null],
            ['id' => 'sample-002', 'name' => 'Sample Product B', 'price02' => 9800, 'image' => 'save_image/img_item01_02.jpg'],
            ['id' => 'admin-active-001', 'name' => '管理画面用 商品A', 'price02' => 3500, 'image' => 'save_image/img_item02_01.jpg'],
            ['id' => 'api-persist-20260522-001', 'name' => '彩のジェラートセット', 'price02' => 2980, 'image' => 'save_image/img_item01_01.jpg'],
            ['id' => 'ui-create-20260522-001', 'name' => 'UI商品登録テスト', 'price02' => 1980, 'image' => 'save_image/img_item02_01.jpg'],
        ];

        $products = [];
        foreach ($rows as $row) {
            $products[] = new EcCubeStub([
                'id' => $row['id'],
                'name' => $row['name'],
                'main_list_image' => $row['image'],
                // No catalog description / ProductClass join in the body:
                // the description <p> is skipped and the single price02
                // renders (not a min–max range).
                'description_list' => null,
                'hasProductClass' => false,
                'getPrice02Min' => $row['price02'],
                'getPrice02Max' => $row['price02'],
                'getPrice02IncTaxMin' => $row['price02'],
                'getPrice02IncTaxMax' => $row['price02'],
                'stock_find' => true,
            ]);
        }

        return $products;
    }

    /**
     * Render EC-CUBE 4.3's real Product/list.twig + default_frame.twig
     * from the gitignored clone, with EC-CUBE's Twig API stubbed. The
     * pagination carries the same five visible products the storefront
     * catalog query projects — the populated `ec-shelfGrid` branch.
     */
    private function renderEcCubeProductList(): string
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

        $products = self::visibleProductRows();

        return $twig->render('Product/list.twig', [
            // Populated result: totalItemCount 5, five product rows.
            // EC-CUBE iterates `pagination` itself for the product grid
            // (explicit iteration set) while its `.totalItemCount` /
            // `.paginationData` properties stay readable; `search_form`
            // iterates ZERO hidden inputs (BeMart's body carries no search
            // form). category_id has no errors, so the normal (else)
            // branch renders.
            'pagination' => new EcCubeStub([
                'totalItemCount' => 5,
                'paginationData' => [],
            ], $products),
            'search_form' => new EcCubeStub([
                'category_id' => new EcCubeStub([
                    'vars' => new EcCubeStub(['errors' => []]),
                ]),
                'vars' => new EcCubeStub(['value' => null]),
                'disp_number' => null,
                'orderby' => null,
            ], []),
            'Category' => null,
            'forms' => [],
            'BaseInfo' => new EcCubeStub(['shop_name' => 'EC-CUBE']),
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
            'app' => new EcCubeStub(['session' => new EcCubeStub([
                'flashbag' => new EcCubeFlashBag(),
            ]), 'request' => new EcCubeStub(['_route' => 'product_list'])]),
            'subtitle' => '商品一覧',
            'title' => '商品一覧',
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
        $twig->addFilter(new TwigFilter('price', static function ($n): string {
            $f = new \NumberFormatter('ja_JP', \NumberFormatter::CURRENCY);

            return (string) $f->formatCurrency((float) ($n ?? 0), 'JPY');
        }));
        $twig->addFilter(new TwigFilter('purify', static fn (string $s): string => $s));
        $twig->addFilter(new TwigFilter('no_image_product', static fn ($s): string => $s ? (string) $s : 'assets/img/common/no_image_product.png'));
        $twig->addFilter(new TwigFilter('filter', static fn ($it, $f): array => []));

        $twig->addFunction(new TwigFunction('trans', $trans));
        $twig->addFunction(new TwigFunction('is_granted', static fn (): bool => false));
        EcCubeAssetStub::register($twig);
        EcCubeRouteStub::register($twig);
        $twig->addFunction(new TwigFunction('csrf_token', static fn (): string => ''));
        $twig->addFunction(new TwigFunction('csrf_token_for_anchor', static fn (): string => ''));
        $twig->addFunction(new TwigFunction('constant', static fn (string $n): string => $n));
        $twig->addFunction(new TwigFunction('template_from_string', static fn (string $s): string => $s));
        $twig->addFunction(new TwigFunction('class_categories_as_json', static fn ($p): string => '{}'));

        // Symfony FormView helpers (only reached inside the populated
        // branch — kept for parse safety).
        $twig->addFunction(new TwigFunction('form_widget', static fn ($f = '', $o = []): string => '[form_widget:' . (is_string($f) ? $f : 'field') . ']'));
        $twig->addFunction(new TwigFunction('form_label', static fn ($f = '', $l = '', $o = []): string => '[form_label:' . (is_string($l) ? $l : 'label') . ']'));
        $twig->addFunction(new TwigFunction('form_errors', static fn ($f = ''): string => ''));
        $twig->addFunction(new TwigFunction('form_rest', static fn ($f = ''): string => ''));
        $twig->addFunction(new TwigFunction('form_row', static fn ($f = '', $o = []): string => '[form_row]'));
        $twig->addFunction(new TwigFunction('has_errors', static fn (...$f): bool => false));
    }

    /**
     * Collapse a rendered HTML document to a list of non-empty,
     * whitespace-trimmed lines for structural line-diffing.
     *
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
