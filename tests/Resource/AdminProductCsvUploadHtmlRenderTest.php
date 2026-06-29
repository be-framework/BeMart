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
 * HTML render verification for the four admin CSV-upload pages
 * (csv-product, csv-category, csv-class-name, csv-class-category).
 *
 * Assertion tiers
 * ---------------
 * L1 — required data present in rendered HTML (csvTitle, field, column spec).
 * L2 — action endpoint, HTTP method, and back-link semantics.
 * Frame — idea-admin shell landmarks present (shell / content).
 */
final class AdminProductCsvUploadHtmlRenderTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    private ResourceInterface $resource;

    /** @return list<array{string, string, string}> uri, form-action, page-title-fragment */
    public static function csvPageProvider(): array
    {
        return [
            [
                'page://self/admin/product/csv-product',
                '/admin/product/csv-product',
                '商品CSV登録',
            ],
            [
                'page://self/admin/product/csv-category',
                '/admin/category/csv',
                'カテゴリCSV登録',
            ],
            [
                'page://self/admin/product/csv-class-name',
                '/admin/product/csv-class-name',
                '規格CSV登録',
            ],
            [
                'page://self/admin/product/csv-class-category',
                '/admin/product/csv-class-category',
                '規格分類CSV登録',
            ],
        ];
    }

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

    /**
     * L1 — required data: HTTP 200, full HTML document, csvTitle, file input.
     *
     * @dataProvider csvPageProvider
     */
    public function testL1RequiredDataPresent(string $uri, string $action, string $titleFragment): void
    {
        $ro   = $this->resource->get($uri);
        $html = $ro->toString();

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);

        // Full HTML document
        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('</body>', $html);

        // L1: page title visible
        $this->assertStringContainsString($titleFragment, $html);

        // L1: file-upload field rendered
        $this->assertStringContainsString('type="file"', $html);
        $this->assertStringContainsString('import_file', $html);

        // L1: column spec reference present (rendered as <table> or <dl> depending on template)
        $this->assertTrue(
            str_contains($html, '<table') || str_contains($html, '<dl'),
            'Column spec markup (<table> or <dl>) must be present',
        );
    }

    /**
     * L2 — action endpoint and HTTP method, back-link semantics.
     *
     * @dataProvider csvPageProvider
     */
    public function testL2ActionAndLinks(string $uri, string $action, string $titleFragment): void
    {
        $html = $this->resource->get($uri)->toString();

        // L2: form posts to the correct endpoint
        $this->assertStringContainsString('method="post"', $html);
        $this->assertStringContainsString('action="' . $action . '"', $html);

        // L2: multipart encoding declared for file upload
        $this->assertStringContainsString('enctype="multipart/form-data"', $html);

        // L2: CSRF field present
        $this->assertStringContainsString('name="csrfToken"', $html);

        // L2: back-link to product list with rel=goProductList
        $this->assertStringContainsString('href="/admin/product-list"', $html);
        $this->assertStringContainsString('rel="goProductList"', $html);
    }

    /**
     * Frame — idea-admin shell landmarks.
     *
     * @dataProvider csvPageProvider
     */
    public function testFrameLandmarks(string $uri, string $action, string $titleFragment): void
    {
        $html = $this->resource->get($uri)->toString();

        $this->assertStringContainsString('class="idea-admin-shell"', $html);
        $this->assertStringContainsString('class="idea-admin-content"', $html);
    }

    // ── csv-category dedicated functional assertions ──────────────────────────

    /** L1: all four column names from AbstractCsvUpload::columns() are rendered. */
    public function testCsvCategoryAllColumnNamesRendered(): void
    {
        $html = $this->resource->get('page://self/admin/product/csv-category')->toString();

        $this->assertStringContainsString('カテゴリID', $html);
        $this->assertStringContainsString('カテゴリ名', $html);
        $this->assertStringContainsString('親カテゴリID', $html);
        $this->assertStringContainsString('カテゴリ削除フラグ', $html);
    }

    /** L2: upload form carries a stable id; skeleton download link present with rel. */
    public function testCsvCategoryFormIdAndSkeletonLink(): void
    {
        $html = $this->resource->get('page://self/admin/product/csv-category')->toString();

        // stable form id for E2E targeting
        $this->assertStringContainsString('id="category-csv-upload-form"', $html);

        // skeleton CSV download href and rel attribute
        $this->assertStringContainsString('href="/admin/category/csv"', $html);
        $this->assertMatchesRegularExpression(
            '/<a\s[^>]*rel="admin_product_csv_category_skeleton"[^>]*>/',
            $html,
        );
    }
}
