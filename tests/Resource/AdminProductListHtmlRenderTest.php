<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Form\AdminProductSearchForm;
use MyVendor\BeMart\Module\HtmlTestModule;
use MyVendor\BeMart\Tests\Resource\Admin\AdminJaMessages;
use MyVendor\BeMart\Tests\Resource\Admin\ProductJaMessages;
use NumberFormatter;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;
use Ray\WebFormModule\FormFactory;
use Twig\Environment;
use Twig\Markup;
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
use function is_string;
use function preg_replace;
use function str_contains;
use function trim;

/**
 * Phase 3 — fidelity check for the admin Product-list HTML port (the
 * Product section's DATA/LIST + search-form page).
 *
 * Same residual-diff standard as the admin pilot {@see AdminNewsListHtmlRenderTest}
 * and the Customer wave {@see AdminCustomerListHtmlRenderTest}: BeMart's
 * templates are PORTS of EC-CUBE 4.3's admin Twig. The page extends
 * `admin-base.html.twig` (the port of EC-CUBE's admin-theme
 * `default_frame.twig`), served via {@see EcCubeAdminStubLoader}.
 *
 *   1. renders EC-CUBE's real `Product/index.twig` + admin frame;
 *   2. renders BeMart's ported `ProductList.html.twig` via the html
 *      context with a seeded admin session;
 *   3. line-diffs the two (whitespace-collapsed);
 *   4. asserts every differing line is in {@see RESIDUAL_ALLOWLIST} or a
 *      residual family.
 *
 * The `id` keyword box is rendered by a real {@see AdminProductSearchForm}
 * on BOTH sides, so it diffs to ZERO.
 */
final class AdminProductListHtmlRenderTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

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
        "'ECCUBE-CSRF-TOKEN': $('meta[name=\"eccube-csrf-token\"]').attr('content')",
        '}',
        '});',
        '});',
        '</script>',
        '<title>商品一覧 商品管理 - BeMart</title>',
        '<title>商品管理 商品一覧 - EC-CUBE</title>',
        // EC-CUBE's `searchDetail` collapse panel adds `{{ has_errors ?
        // ' show' }}` — BeMart's port has no `has_errors` body field
        // (no server-side search-error replay in the Wave 8 slice), so
        // the class list differs by the trailing ` show` toggle.
        '<div class="c-subContents collapse ec-collapse" id="searchDetail">',
        '<div class="c-subContents collapse ec-collapse " id="searchDetail">',
        // EC-CUBE's omitted `pageMaxis` page-count <select> sits inside a
        // bare `<div>` wrapper (`d-inline-block me-2 align-bottom` >
        // `<div>` > `<select>`). BeMart omits the whole pageMaxis block
        // (no server-side paging — Wave 8 slice); the lone inner `<div>`
        // is EC-CUBE-only.
        '<div>',
    ];

    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $meta = new Meta('MyVendor\\BeMart', 'html');
        $module = new HtmlTestModule($meta);
        $session = new FakeAdminSession(self::TEST_ADMIN_ID);
        $module->override(new class ($session) extends AbstractModule {
            public function __construct(private readonly FakeAdminSession $session)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(AdminSessionInterface::class)->toInstance($this->session);
            }
        });
        $injector = new Injector($module, dirname(__DIR__, 2) . '/var/tmp/html');
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    public function testProductListRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/admin/product-list');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('<div class="c-container">', $html);
        $this->assertStringContainsString('</body>', $html);

        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    public function testProductListPreservesEcCubeAdminMarkupStructure(): void
    {
        $html = $this->resource->get('page://self/admin/product-list')->toString();

        foreach ([
            '<header class="c-headerBar">',
            '<div class="c-mainNavArea">',
            '<div class="c-contentsArea">',
            '<div class="c-pageTitle">',
            'class="c-outsideBlock"',
            'id="searchDetail"',
            'class="c-contentsArea__cols"',
            'class="c-primaryCol"',
            '<table class="table table-sm">',
            'id="form_bulk"',
            'id="bulkDeleteModal"',
        ] as $needle) {
            $this->assertStringContainsString($needle, $html, "ported markup missing: {$needle}");
        }
    }

    public function testProductListRendersSeededProductRows(): void
    {
        $html = $this->resource->get('page://self/admin/product-list')->toString();

        $this->assertStringContainsString('id="admin_search_product_id"', $html);
        $this->assertStringContainsString('検索結果：', $html);
        $this->assertStringContainsString('サンプル商品 A', $html);
    }

    /**
     * The honesty test: diff BeMart's rendered admin Product list against
     * EC-CUBE's own rendering. Every difference must be in the residual
     * allowlist or a residual family.
     */
    public function testProductListHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $beMart = $this->resource->get('page://self/admin/product-list')->toString();
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
            "BeMart's admin Product-list HTML diverged from EC-CUBE's "
            . "beyond the residual allowlist. Unexplained diff lines:\n  "
            . implode("\n  ", $unexplained)
            . "\n\n(only-in-EC-CUBE: " . count($onlyInEcCube)
            . ', only-in-BeMart: ' . count($onlyInBeMart) . ')',
        );

        $this->assertLessThanOrEqual(
            120,
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
            // EC-CUBE-runtime <head> furniture.
            'eccube-csrf-token',
            '<title>',
            'c-headerBar__shopTitle',
            'c-headerBar__userMenu',
            'data-bs-content',
            'last_login',
            // Admin frame: the DYNAMIC sidebar nav (eccubeNav tree).
            'nav-',
            'data-bs-toggle="collapse"',
            'fa-fw',
            // Product list: EC-CUBE keys product rows by the surrogate
            // int `Product.id`; BeMart's ProductListFetched projection
            // keys by `productCode` (no surrogate id in the Wave 8
            // slice). The row `id`, the checkbox value / delete-url, the
            // edit / copy / detail anchors all carry the productCode in
            // place of the int id — same elements, different id token.
            'ex-product-',
            'confirmModal-',
            'admin_product_product_edit',
            'admin_product_product_delete',
            'admin_product_product_copy',
            'product_detail?id=',
            'id="check_',
            'name="ids[]"',
            // Product list: EC-CUBE's image thumbnail `<img>` — the
            // projection does not carry product images (Wave 8
            // shallow-grid slice). FLAGGED for enrichment follow-up.
            'no_image_product',
            'max-width: 50px',
            '<img src=',
            // Product list: EC-CUBE renders the ProductClass code / price
            // min-max aggregates + the create/update timestamps + the
            // master-data display-status NAME. The projection carries a
            // flat productCode / price02 / productStatus / no timestamps
            // (Wave 8 shallow-grid slice — see the port header). BeMart
            // renders the single value; EC-CUBE the range / formatted
            // date / status label. FLAGGED for enrichment follow-up.
            'btn page-link',
            'data-class-load',
            'data-class-url',
            // Product list: EC-CUBE's bulk-status buttons carry a
            // `csrf_token_for_anchor()` widget + a master-data
            // ProductStatus constant in the `id` query param. BeMart's
            // port links to the bare route. Same buttons, same labels.
            'admin_product_bulk_product_status',
            'token-for-anchor',
            // Product list: EC-CUBE's CSV-setting link passes the
            // `CsvType::CSV_TYPE_PRODUCT` constant as the `id` query
            // param; BeMart's port links to the bare route. Same anchor
            // + label, different query string.
            'admin_setting_shop_csv',
            // Product list JS: EC-CUBE injects the CSRF token hidden
            // input named by the `Constant::TOKEN_NAME` PHP constant
            // (via the `constant()` Twig fn). BeMart has no `constant`
            // helper and no per-request CSRF widget; the port names the
            // input `_token` literally. The EC-CUBE render stub returns
            // the constant NAME, so the `.attr('name', ...)` line differs.
            "attr('name',",
            'Constant::TOKEN_NAME',
            // Product list: EC-CUBE's `pageMaxis` per-page-count <select>
            // dropdown + the `@admin/pager.twig` wrapper. No server-side
            // paging in the Wave 8 slice — the dropdown + pager row +
            // their wrapping cells are OMITTED.
            'd-inline-block me-2 align-bottom',
            'form-select',
            '</select>',
            'justify-content-md-center',
            // Product list: the empty-result branch. EC-CUBE has a
            // three-way `pagination / has_errors / else`; BeMart's port
            // has a two-way `count / else`. With seeded rows the
            // populated branch is taken on both sides, so this family is
            // defensive.
            'search_invalid_condition',
        ] as $family) {
            if (str_contains($line, $family)) {
                return true;
            }
        }

        return false;
    }

    private function renderEcCube(): string
    {
        $adminTemplates = dirname(__DIR__, 2)
            . '/tools/ec-cube-source/src/Eccube/Resource/template/admin';
        if (! is_dir($adminTemplates)) {
            $this->markTestSkipped('EC-CUBE 4.3 reference clone not present.');
        }

        $twig = new Environment(new EcCubeAdminStubLoader($adminTemplates), [
            'autoescape' => 'html',
            'strict_variables' => false,
        ]);

        $searchForm = (new FormFactory())->newInstance(AdminProductSearchForm::class);
        if ($searchForm instanceof AdminProductSearchForm) {
            $searchForm->fillFilters([]);
        }

        $this->registerEcCubeStubs($twig, $searchForm);

        // The same logical product list as BeMart's ProductQueryInterface
        // seed, projected onto the EC-CUBE row shape.
        $beMartList = $this->resource->get('page://self/admin/product-list');
        $rows = [];
        foreach ($beMartList->body['products'] as $product) {
            $rows[] = new EcCubeStub([
                'id' => $product['productCode'],
                'name' => $product['productName'],
                'code_min' => $product['productCode'],
                'code_max' => $product['productCode'],
                'price02_min' => $product['price02'],
                'price02_max' => $product['price02'],
                'hasProductClass' => false,
                'stockunlimited_min' => $product['stock'] === null,
                'stock_min' => $product['stock'] ?? 0,
                'status' => new EcCubeStub(['name' => (string) $product['productStatus']]),
                'mainFileName' => null,
                'create_date' => '',
                'update_date' => '',
            ]);
        }

        return $twig->render('Product/index.twig', [
            'searchForm' => new EcCubeStub([
                '_token' => '_token',
                'id' => 'id',
                'category_id' => 'category_id',
                'status' => 'status',
                'stock' => 'stock',
                'tag_id' => 'tag_id',
                'create_datetime_start' => 'create_datetime_start',
                'create_datetime_end' => 'create_datetime_end',
                'update_datetime_start' => 'update_datetime_start',
                'update_datetime_end' => 'update_datetime_end',
                'sortkey' => 'sortkey',
                'sorttype' => 'sorttype',
            ], []),
            'pagination' => new EcCubeStub([
                'totalItemCount' => count($rows),
                'paginationData' => new EcCubeStub(['pageCount' => 1]),
            ], $rows),
            'pageMaxis' => [],
            'page_count' => 50,
            'has_errors' => false,
            'BaseInfo' => new EcCubeStub(['shop_name' => 'EC-CUBE']),
            'eccube_config' => [
                'locale' => 'ja',
                'eccube_official_site_url' => 'https://www.ec-cube.net/',
                'eccube_community_site_url' => 'https://xoo.ps/eccube/',
                'eccube_document_url' => 'https://doc4.ec-cube.net/',
                'eccube_manual_url' => 'https://www.ec-cube.net/product/',
            ],
            'eccubeNav' => [],
            'menus' => ['product', 'product_master'],
            'plugin_assets' => [],
            'plugin_snippets' => [],
            'app' => new EcCubeStub([
                'user' => new EcCubeStub([
                    'name' => '管理者',
                    'login_date' => '2026-05-20 10:00:00',
                    'two_factor_auth_enabled' => false,
                ]),
                'request' => new EcCubeStub(['_route' => 'admin_product']),
            ]),
            'subtitle' => '商品管理',
            'sub_title' => '商品管理',
            'title' => '商品一覧',
        ]);
    }

    private function registerEcCubeStubs(Environment $twig, AdminProductSearchForm|null $searchForm): void
    {
        $messages = AdminJaMessages::forSection(ProductJaMessages::keys());
        $trans = static function (string $key, array $params = []) use ($messages): string {
            $text = $messages[$key] ?? $key;
            foreach ($params as $name => $value) {
                $text = str_replace($name, (string) $value, $text);
            }

            return $text;
        };
        $jpy = new NumberFormatter('ja_JP', NumberFormatter::CURRENCY);
        $price = static fn ($value): string => (string) $jpy->formatCurrency((float) $value, 'JPY');

        $twig->addFilter(new TwigFilter('trans', $trans));
        $twig->addFilter(new TwigFilter('date_sec', static fn ($d): string => (string) $d));
        $twig->addFilter(new TwigFilter('date_min', static fn ($d): string => (string) $d));
        $twig->addFilter(new TwigFilter('number_format', static fn ($d): string => (string) $d));
        $twig->addFilter(new TwigFilter('price', $price));
        $twig->addFilter(new TwigFilter('no_image_product', static fn ($d): string => (string) $d));

        $twig->addFunction(new TwigFunction('trans', $trans));
        $twig->addFunction(new TwigFunction('is_granted', static fn (): bool => false));
        EcCubeAssetStub::register($twig);
        EcCubeRouteStub::register($twig);
        $twig->addFunction(new TwigFunction('csrf_token', static fn (): string => ''));
        $twig->addFunction(new TwigFunction('csrf_token_for_anchor', static fn (): string => ''));
        $twig->addFunction(new TwigFunction('constant', static fn (string $n): string => $n));
        $twig->addFunction(new TwigFunction('active_menus', static fn (): array => ['', '', '']));

        $twig->addFunction(new TwigFunction('form_widget', static function ($field = '', $opts = []) use ($searchForm): Markup {
            if ($searchForm instanceof AdminProductSearchForm && is_string($field) && $field === 'id') {
                return new Markup($searchForm->input('id'), 'UTF-8');
            }

            return new Markup('', 'UTF-8');
        }));
        $twig->addFunction(new TwigFunction('form_errors', static fn ($f = ''): string => ''));
        $twig->addFunction(new TwigFunction('form_label', static fn ($f = '', $l = '', $o = []): string => ''));
        $twig->addFunction(new TwigFunction('form_row', static fn ($f = '', $o = []): string => ''));
        $twig->addFunction(new TwigFunction('form_rest', static fn ($f = ''): string => ''));
        $twig->addFunction(new TwigFunction('has_errors', static fn (...$f): bool => false));
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
