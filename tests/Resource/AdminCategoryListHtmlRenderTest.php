<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Tests\Support\HtmlTestInjector;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;

/**
 * HTML render tests for the admin category list page.
 *
 * L0 — HTTP 200 + text/html content-type.
 * L1 — Required data fields are present in the rendered output.
 * L2 — Action endpoints, HTTP methods, and link relations are correct.
 * L3 — Page landmark uses idea-admin-shell / idea-admin-content vocabulary.
 *
 * EC-CUBE parity checks are not applicable: this template is a clean-room
 * redesign using idea-admin-* vocabulary. Any EC-CUBE-specific assertion
 * should be added under @group ec-cube-parity-archived + markTestSkipped.
 */
final class AdminCategoryListHtmlRenderTest extends TestCase
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

    private function fetchHtml(): string
    {
        return $this->resource->get('page://self/admin/category/category-list')->toString();
    }

    // ── L0 ───────────────────────────────────────────────────────────────────

    /** L0 — HTTP 200 with correct content-type. */
    public function testRendersOkWithHtmlContentType(): void
    {
        $ro = $this->resource->get('page://self/admin/category/category-list');

        $this->assertSame(Code::OK, $ro->code);
        $ro->toString();
        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    // ── L1 — Required data fields ─────────────────────────────────────────────

    /**
     * L1 — Fake seed returns two root categories ("Food", "Drinks").
     * Both category names must appear in the rendered output.
     */
    public function testFakeCategoryNamesAreRendered(): void
    {
        $html = $this->fetchHtml();

        $this->assertStringContainsString('Food', $html);
        $this->assertStringContainsString('Drinks', $html);
    }

    /** L1 — Total count is rendered (Fake seed has 2 entries). */
    public function testCountIsRendered(): void
    {
        $html = $this->fetchHtml();

        // Count appears as "2件" somewhere in the page.
        $this->assertMatchesRegularExpression('/2[^\d]*件/', $html);
    }

    /**
     * L1 — The quick-create form has all required input fields:
     *       categoryName, parentId (optional select), sortNo.
     */
    public function testCreateFormFieldsArePresent(): void
    {
        $html = $this->fetchHtml();

        $this->assertMatchesRegularExpression('/name=["\']categoryName["\']/', $html);
        $this->assertMatchesRegularExpression('/name=["\']parentId["\']/', $html);
        $this->assertMatchesRegularExpression('/name=["\']sortNo["\']/', $html);
    }

    /** L1 — CSRF token hidden field is present in the create form. */
    public function testCreateFormCsrfTokenFieldIsPresent(): void
    {
        $html = $this->fetchHtml();

        $this->assertMatchesRegularExpression('/name=["\']csrfToken["\']/', $html);
    }

    // ── L2 — Action endpoints and link relations ──────────────────────────────

    /**
     * L2 — Create form targets the correct endpoint with POST method.
     *       doCreateCategory: POST /admin/category/category-list
     */
    public function testCreateFormActionIsCorrect(): void
    {
        $html = $this->fetchHtml();

        $this->assertStringContainsString('action="/admin/category/category-list"', $html);
        $this->assertMatchesRegularExpression('/method=["\']post["\']/', $html);
    }

    /**
     * L2 — Each category row carries a link to the edit page (goCategory).
     *       href="/admin/category/category?categoryId=…"
     */
    public function testEditLinksCarryGoCategoryHref(): void
    {
        $html = $this->fetchHtml();

        $this->assertStringContainsString('href="/admin/category/category?categoryId=cat-food"', $html);
        $this->assertStringContainsString('href="/admin/category/category?categoryId=cat-drinks"', $html);
    }

    /**
     * L2 — Edit links carry the rel="goCategory" relation attribute.
     */
    public function testEditLinksCarryGoCategoryRel(): void
    {
        $html = $this->fetchHtml();

        $this->assertStringContainsString('rel="goCategory"', $html);
    }

    /**
     * L2 — Delete forms carry method override "_method=delete" and the
     *       category ID.  doDeleteCategory: DELETE /admin/category/category
     */
    public function testDeleteFormsCarryMethodOverrideAndCategoryId(): void
    {
        $html = $this->fetchHtml();

        $this->assertStringContainsString('name="_method" value="delete"', $html);
        $this->assertStringContainsString('cat-food', $html);
    }

    /**
     * L2 — CSV export link targets GET /admin/category/csv (goExportCategory).
     */
    public function testCsvExportLinkIsPresent(): void
    {
        $html = $this->fetchHtml();

        $this->assertStringContainsString('href="/admin/category/csv"', $html);
        $this->assertStringContainsString('rel="goExportCategory"', $html);
    }

    // ── L3 — Frame landmarks ──────────────────────────────────────────────────

    /** L3 — Page uses idea-admin-shell landmark (provided by admin-base). */
    public function testPageUsesIdeaAdminShellLandmark(): void
    {
        $html = $this->fetchHtml();

        $this->assertStringContainsString('idea-admin-shell', $html);
    }

    /** L3 — Page uses idea-admin-content landmark (provided by admin-base). */
    public function testPageUsesIdeaAdminContentLandmark(): void
    {
        $html = $this->fetchHtml();

        $this->assertStringContainsString('idea-admin-content', $html);
    }

    /**
     * EC-CUBE parity checks are not applicable to this clean-room template.
     *
     * @group ec-cube-parity-archived
     */
    public function testEcCubeParityArchived(): void
    {
        $this->markTestSkipped(
            'EC-CUBE markup parity not applicable: CategoryList.html.twig is a clean-room ' .
            'idea-admin-* redesign. No EC-CUBE reference DOM to compare against. ' .
            'Archived under group ec-cube-parity-archived.'
        );
    }
}
