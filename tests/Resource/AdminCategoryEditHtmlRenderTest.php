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
 * Phase 3 — HTML render verification for the admin カテゴリ登録 / 編集 page
 * (page://self/admin/category/edit → var/templates/Page/Admin/Category/Edit.html.twig).
 *
 * Semantic check layers:
 *   L0  — HTTP code + Content-Type
 *   L1  — Required data / form fields are rendered
 *   L2  — Action endpoints, HTTP method tunnels, and link relations
 *   Frame — idea-admin-shell / content landmarks
 *
 * EC-CUBE DOM / class parity checks are archived; see testEcCubeParityArchived().
 */
final class AdminCategoryEditHtmlRenderTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';
    private const TEST_CATEGORY_ID = 'cat-food';

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

    // ── L0: HTTP + document shell ────────────────────────────────────────────

    /** New-form variant: GET /admin/category/edit (no categoryId). */
    public function testNewFormRendersWithHttpOk(): void
    {
        $ro = $this->resource->get('page://self/admin/category/edit');

        $this->assertSame(Code::OK, $ro->code);
        $ro->toString();
        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    /** Edit-form variant: GET /admin/category/edit?categoryId=cat-food. */
    public function testEditFormRendersWithHttpOk(): void
    {
        $ro = $this->resource->get('page://self/admin/category/edit', [
            'categoryId' => self::TEST_CATEGORY_ID,
        ]);

        $this->assertSame(Code::OK, $ro->code);
    }

    public function testRendersFullHtmlDocument(): void
    {
        $html = $this->resource->get('page://self/admin/category/edit')->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('</body>', $html);
    }

    // ── Frame: idea-admin shell landmarks ───────────────────────────────────

    public function testIdeaAdminShellLandmarkPresent(): void
    {
        $html = $this->resource->get('page://self/admin/category/edit')->toString();

        $this->assertStringContainsString('idea-admin-shell', $html, 'shell landmark missing');
    }

    public function testIdeaAdminContentLandmarkPresent(): void
    {
        $html = $this->resource->get('page://self/admin/category/edit')->toString();

        $this->assertStringContainsString('idea-admin-content', $html, 'content landmark missing');
    }

    public function testIdeaAdminToolbarPresent(): void
    {
        $html = $this->resource->get('page://self/admin/category/edit')->toString();

        $this->assertStringContainsString('idea-admin-toolbar', $html, 'toolbar landmark missing');
    }

    // ── L1: required data fields rendered ────────────────────────────────────

    /** Form element is present with the contract-defined id. */
    public function testFormElementIsPresent(): void
    {
        $html = $this->resource->get('page://self/admin/category/edit')->toString();

        $this->assertStringContainsString('id="category_form"', $html, 'form#category_form missing');
    }

    /** name field (required in AdminCategoryForm) must be rendered. */
    public function testNameFieldIsRendered(): void
    {
        $html = $this->resource->get('page://self/admin/category/edit')->toString();

        $this->assertStringContainsString('admin_category_name', $html, 'name field id missing');
    }

    /** parent_id field must be rendered. */
    public function testParentIdFieldIsRendered(): void
    {
        $html = $this->resource->get('page://self/admin/category/edit')->toString();

        $this->assertStringContainsString('admin_category_parent_id', $html, 'parent_id field missing');
    }

    /** sort_no field must be rendered. */
    public function testSortNoFieldIsRendered(): void
    {
        $html = $this->resource->get('page://self/admin/category/edit')->toString();

        $this->assertStringContainsString('admin_category_sort_no', $html, 'sort_no field missing');
    }

    /** Category list section or empty-state placeholder must be present. */
    public function testCategoryListSectionIsPresent(): void
    {
        $html = $this->resource->get('page://self/admin/category/edit')->toString();

        $this->assertMatchesRegularExpression(
            '/カテゴリ一覧|category_table|idea-admin-empty/',
            $html,
            'category list section missing',
        );
    }

    // ── L2: action endpoints and HTTP methods ────────────────────────────────

    /**
     * New-mode: form action must target the collection endpoint
     * (doCreateCategory — POST /admin/category/category-list).
     */
    public function testNewFormActionTargetsCollectionEndpoint(): void
    {
        $html = $this->resource->get('page://self/admin/category/edit')->toString();

        $this->assertStringContainsString(
            'action="/admin/category/category-list"',
            $html,
            'create form must target collection endpoint (doCreateCategory)',
        );
    }

    /**
     * Edit-mode: form action must target the single-row endpoint with
     * _method=put tunnel (doUpdateCategory — PUT /admin/category/category).
     */
    public function testEditFormActionTargetsItemEndpointWithPutTunnel(): void
    {
        $html = $this->resource->get('page://self/admin/category/edit', [
            'categoryId' => self::TEST_CATEGORY_ID,
        ])->toString();

        $this->assertStringContainsString(
            '/admin/category/category',
            $html,
            'update form must target single-row endpoint',
        );
        $this->assertStringContainsString(
            '_method=put',
            $html,
            'update form must include _method=put tunnel',
        );
    }

    /**
     * Edit-mode: delete affordance must be present with _method=delete tunnel
     * and carry the categoryId in the form action URL
     * (doDeleteCategory — DELETE /admin/category/category).
     */
    public function testEditModeDeleteAffordancePresent(): void
    {
        $html = $this->resource->get('page://self/admin/category/edit', [
            'categoryId' => self::TEST_CATEGORY_ID,
        ])->toString();

        $this->assertStringContainsString(
            '_method=delete',
            $html,
            'delete form must include _method=delete tunnel',
        );
        $this->assertStringContainsString(
            'categoryId=' . self::TEST_CATEGORY_ID,
            $html,
            'delete form must carry categoryId in action URL',
        );
    }

    /**
     * Navigation back-link must lead to goCategoryList endpoint.
     * rel=goCategoryList (Edit resource #[Link]).
     */
    public function testBackLinkLeadsToCategoryList(): void
    {
        $html = $this->resource->get('page://self/admin/category/edit')->toString();

        $this->assertStringContainsString(
            '/admin/category/category-list',
            $html,
            'back-link href must target goCategoryList endpoint',
        );
    }

    /**
     * CSRF token hidden input must be present in the mutation form.
     */
    public function testCsrfTokenHiddenInputPresent(): void
    {
        $html = $this->resource->get('page://self/admin/category/edit')->toString();

        $this->assertStringContainsString('name="csrfToken"', $html, 'csrfToken hidden field missing');
    }

    /**
     * In edit mode: categoryId hidden field must be present in the form.
     */
    public function testEditModeFormCarriesCategoryIdHidden(): void
    {
        $html = $this->resource->get('page://self/admin/category/edit', [
            'categoryId' => self::TEST_CATEGORY_ID,
        ])->toString();

        $this->assertStringContainsString(
            'name="categoryId"',
            $html,
            'categoryId hidden field must be present in edit-mode form',
        );
    }

    // ── L2: per-row edit links in the list ───────────────────────────────────

    /**
     * When categories exist, each row must link to the edit endpoint
     * (goCategory transition — GET /admin/category/edit?categoryId=…).
     */
    public function testCategoryListRowsLinkToEditPage(): void
    {
        $ro = $this->resource->get('page://self/admin/category/edit');
        /** @var array{categories?: array<int, mixed>} $body */
        $body = $ro->body;

        if (empty($body['categories'])) {
            $this->markTestSkipped('Fake storage has no categories; row-link check skipped.');
        }

        $html = $ro->toString();
        $this->assertStringContainsString(
            '/admin/category/edit?categoryId=',
            $html,
            'each category row must link to the edit endpoint (goCategory)',
        );
    }

    // ── Archived: EC-CUBE parity ─────────────────────────────────────────────

    /**
     * EC-CUBE markup parity checks (c-contentsArea, c-headerBar,
     * c-primaryCol, btn-ec-conversion, token-for-anchor) were retired
     * when Edit.html.twig was rewritten as an idea-admin clean-room design.
     *
     * @group ec-cube-parity-archived
     */
    public function testEcCubeParityArchived(): void
    {
        $this->markTestSkipped(
            'EC-CUBE DOM/class parity (c-*, btn-ec-*, token-for-anchor) retired ' .
            'in the idea-admin clean-room redesign. Archived: @group ec-cube-parity-archived.',
        );
    }
}
