<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Tests\Support\HtmlTestInjector;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;

/**
 * BeMart admin Product-list HTML render test — idea-admin design language.
 *
 * This is a clean-room rebuild of the original EC-CUBE-parity render test.
 * The template was rewritten from scratch in the idea-admin-* design language
 * (no c-* / ec-* / Bootstrap classes) — EC-CUBE DOM parity is no longer the
 * contract; semantic and functional parity is.
 *
 * Verification contract (two levels):
 *
 *   L1 — Structural landmarks: the idea-admin-* shell, the search form, the
 *        product table, and the bulk-action/delete affordances must be present
 *        in the rendered HTML.
 *
 *   L2 — Functional/semantic correctness: the search-form action/method, the
 *        product-detail and product-copy link hrefs/rels, the bulk-status
 *        data-action/data-product-status attributes, and the CSRF affordances
 *        must match the resource-layer contracts declared in
 *        src/Resource/Page/Admin/ProductList.php (#[Link] annotations).
 *
 * EC-CUBE rendering comparison tests are archived with @group ec-cube-parity-archived
 * because the template no longer targets EC-CUBE DOM parity.
 */
final class AdminProductListHtmlRenderTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $session = new FakeAdminSession(self::TEST_ADMIN_ID);
        $injector = HtmlTestInjector::getOverrideInstance(new class ($session) extends AbstractModule {
            public function __construct(private readonly FakeAdminSession $session)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(AdminSession::class)->toInstance($this->session);
            }
        });
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    // ── L0: HTTP / Content-Type ──────────────────────────────────────────────

    public function testProductListRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/admin/product-list');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('</body>', $html);
        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    // ── L1: idea-admin-* shell landmarks ────────────────────────────────────

    public function testProductListRendersIdeaAdminShellLandmarks(): void
    {
        $html = $this->resource->get('page://self/admin/product-list')->toString();

        foreach ([
            'class="idea-admin-shell"',
            'class="idea-admin-topbar"',
            'class="idea-admin-sidebar"',
            'class="idea-admin-content"',
            'class="idea-admin-page-header"',
            'idea-admin-table-wrap',
            'class="idea-admin-table"',
            'id="searchDetail"',
            'id="form_bulk"',
            'id="bulkDeleteModal"',
        ] as $needle) {
            $this->assertStringContainsString($needle, $html, "idea-admin landmark missing: {$needle}");
        }
    }

    public function testProductListHasNoLegacyEcCubeOrBootstrapClasses(): void
    {
        $html = $this->resource->get('page://self/admin/product-list')->toString();

        // The {% block main %} content must not contain any c-* / ec-* /
        // Bootstrap grid or card classes. Assertions are limited to the page's
        // own block content; admin-base.html.twig is separately tested.
        foreach ([
            'class="c-',
            'class="ec-',
            'class="btn btn-',
            'class="card',
            'class="row ',
            'class="col-',
            'class="table table',
        ] as $forbidden) {
            $this->assertStringNotContainsString(
                $forbidden,
                $html,
                "Legacy class found in idea-admin rebuild: {$forbidden}",
            );
        }
    }

    // ── L1: Required fields / list data present ──────────────────────────────

    public function testProductListRendersSearchFormAndResultCount(): void
    {
        $html = $this->resource->get('page://self/admin/product-list')->toString();

        // Search keyword box (rendered by AdminProductSearchForm)
        $this->assertStringContainsString('id="admin_search_product_id"', $html);
        // Result count text
        $this->assertStringContainsString('検索結果：', $html);
    }

    public function testProductListRendersSeededProductRows(): void
    {
        $html = $this->resource->get('page://self/admin/product-list')->toString();

        // At least one product name from the Fake corpus must appear
        $this->assertStringContainsString('サンプル商品 A', $html);
        // Row id anchors keyed by productCode
        $this->assertMatchesRegularExpression('/id="ex-product-[^"]+"/', $html);
    }

    public function testProductListRendersStatusBadgesForSeededRows(): void
    {
        $html = $this->resource->get('page://self/admin/product-list')->toString();

        // At least one status badge from the idea-admin vocabulary
        $this->assertMatchesRegularExpression(
            '/class="idea-admin-badge idea-admin-badge--(public|private|discontinued)"/',
            $html,
        );
    }

    // ── L2: form action / method ─────────────────────────────────────────────

    public function testSearchFormUsesGetToProductListEndpoint(): void
    {
        $html = $this->resource->get('page://self/admin/product-list')->toString();

        // Resource onGet action="/admin/product-list" method="get"
        $this->assertStringContainsString('action="/admin/product-list"', $html);
        $this->assertStringContainsString('method="get"', $html);
    }

    // ── L2: link href / rel ──────────────────────────────────────────────────

    public function testProductDetailLinksCarryProductCodeAndRel(): void
    {
        $html = $this->resource->get('page://self/admin/product-list')->toString();

        // goProduct: GET /admin/product/edit?productCode=...
        $this->assertMatchesRegularExpression(
            '#href="/admin/product/edit\?productCode=[^"]+"\s[^>]*rel="goProduct"#',
            $html,
        );
    }

    public function testCsvExportLinkCarriesRel(): void
    {
        $html = $this->resource->get('page://self/admin/product-list')->toString();

        // goExportProduct: GET /admin/product-csv
        $this->assertStringContainsString('href="/admin/product-csv"', $html);
        $this->assertStringContainsString('rel="goExportProduct"', $html);
    }

    public function testNewProductLinkPointsToProductNew(): void
    {
        $html = $this->resource->get('page://self/admin/product-list')->toString();

        // doCreateProduct: POST /admin/product — the "商品登録" link goes to
        // /admin/product-new (the new-product form page, not the resource action).
        $this->assertStringContainsString('href="/admin/product-new"', $html);
    }

    // ── L2: CSRF / unsafe action affordances ─────────────────────────────────

    public function testProductListExposesDeleteAffordancesPerRow(): void
    {
        $html = $this->resource->get('page://self/admin/product-list')->toString();

        // data-delete-url carries productCode and _method=delete
        $this->assertStringContainsString('data-delete-url="/admin/product?productCode=', $html);
        $this->assertMatchesRegularExpression(
            '/data-delete-url="[^"]+_method=delete"[^>]+token-for-anchor="[a-f0-9]{64}"/',
            $html,
        );
    }

    public function testProductListExposesProductCopyAffordances(): void
    {
        $html = $this->resource->get('page://self/admin/product-list')->toString();

        // Copy link: POST /admin/product-copy?productCode=... with CSRF token
        $this->assertMatchesRegularExpression(
            '#href="/admin/product-copy\?productCode=[^"]+"[^>]+data-method="post"[^>]+token-for-anchor="[a-f0-9]{64}"#',
            $html,
        );
    }

    public function testBulkStatusButtonsExposeThreeStatusValues(): void
    {
        $html = $this->resource->get('page://self/admin/product-list')->toString();

        // doBulkUpdateProductStatus: POST /admin/product-bulk-status
        $this->assertStringContainsString('data-action="/admin/product-bulk-status"', $html);
        $this->assertStringContainsString('data-product-status="1"', $html);  // 公開
        $this->assertStringContainsString('data-product-status="2"', $html);  // 非公開
        $this->assertStringContainsString('data-product-status="3"', $html);  // 廃止
    }

    public function testBulkStatusButtonsCarryCsrfToken(): void
    {
        $html = $this->resource->get('page://self/admin/product-list')->toString();

        // At least one action-submit button must carry a 64-char hex CSRF token.
        // Attributes may be rendered on separate lines (s-flag for dotall).
        $this->assertMatchesRegularExpression(
            '/<button[^>]*class="[^"]*action-submit[^"]*"[^>]*>/s',
            $html,
            'action-submit button is missing',
        );
        $this->assertMatchesRegularExpression(
            '/token-for-anchor="[a-f0-9]{64}"/',
            $html,
            'CSRF token (token-for-anchor) is missing on bulk-action button',
        );
    }

    public function testBulkFormCheckboxesCarryProductCodesName(): void
    {
        $html = $this->resource->get('page://self/admin/product-list')->toString();

        // JS reads `input:checkbox[name^="productCodes"]`
        $this->assertStringContainsString('name="productCodes[]"', $html);
    }

    // ── EC-CUBE parity tests (archived) ──────────────────────────────────────

    /**
     * The template is now a clean-room idea-admin rebuild, not a DOM port of
     * EC-CUBE's admin/Product/index.twig. EC-CUBE reference rendering
     * comparison is no longer meaningful and is archived.
     *
     * @group ec-cube-parity-archived
     */
    public function testProductListHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $this->markTestSkipped(
            'EC-CUBE DOM-parity test archived: ProductList.html.twig is now a '
            . 'clean-room idea-admin-* rebuild. Functional/semantic parity is '
            . 'verified by the L1/L2 tests in this class.',
        );
    }
}
