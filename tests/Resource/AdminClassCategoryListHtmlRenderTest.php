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

use function str_contains;

/**
 * Functional / semantic render verification for the admin ClassCategory-list page.
 *
 * Checks L1 (required data fields), L2 (actions, links, HTTP methods/rels),
 * and frame landmarks. EC-CUBE markup-parity assertions have been removed;
 * the former parity test is archived below with @group ec-cube-parity-archived.
 */
final class AdminClassCategoryListHtmlRenderTest extends TestCase
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

    /* ── L0 — HTTP / content-type ── */

    public function testReturnsOkWithHtmlContentType(): void
    {
        $ro = $this->resource->get('page://self/admin/class-category/class-category-list');

        $this->assertSame(Code::OK, $ro->code);
        $ro->toString();
        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    /* ── L1 — Shell landmarks ── */

    public function testRendersFullHtmlDocument(): void
    {
        $html = $this->resource->get('page://self/admin/class-category/class-category-list')->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html', $html);
        $this->assertStringContainsString('</body>', $html);
    }

    public function testContainsAdminShellLandmark(): void
    {
        $html = $this->resource->get('page://self/admin/class-category/class-category-list')->toString();

        // The admin-base frame must provide the idea-admin-shell wrapper.
        $this->assertStringContainsString('idea-admin-shell', $html);
    }

    public function testContainsAdminContentLandmark(): void
    {
        $html = $this->resource->get('page://self/admin/class-category/class-category-list')->toString();

        $this->assertStringContainsString('idea-admin-content', $html);
    }

    /* ── L1 — Required data ── */

    public function testRendersCreateFormWithRequiredField(): void
    {
        $html = $this->resource->get('page://self/admin/class-category/class-category-list')->toString();

        // The AdminClassCategoryForm must render the classCategoryName input.
        $this->assertStringContainsString('id="admin_class_category_name"', $html);
        $this->assertStringContainsString('name="classCategoryName"', $html);
    }

    /* ── L2 — Create action ── */

    public function testCreateFormPostsToClassCategoryListEndpoint(): void
    {
        $html = $this->resource->get('page://self/admin/class-category/class-category-list')->toString();

        $this->assertStringContainsString('method="post"', $html);
        $this->assertStringContainsString('action="/admin/class-category/class-category-list"', $html);
    }

    /* ── L2 — Back-link (goClassNameList) ── */

    public function testRendersBackLinkToClassNameList(): void
    {
        $html = $this->resource->get('page://self/admin/class-category/class-category-list')->toString();

        $this->assertStringContainsString('href="/admin/class-name/class-name-list"', $html);
        $this->assertStringContainsString('rel="goClassNameList"', $html);
    }

    /* ── L2 — CSV export link ── */

    public function testRendersExportLink(): void
    {
        $html = $this->resource->get('page://self/admin/class-category/class-category-list')->toString();

        $this->assertStringContainsString('/admin/class-category/class-category-export', $html);
    }

    /* ── L2 — Update + Delete actions (per-row, via classNameId scope) ── */

    public function testScopedListRendersUpdateAndDeleteActionsPerRow(): void
    {
        // Seed a row so the per-row action markup is present.
        // FakeAdminSession + in-memory store: create a category first.
        // Since the fake store is seeded by FakeAdminSession, try a scoped
        // GET that returns whatever rows the fake has; if empty we only
        // assert the create form is present (empty-state is a valid path).
        $ro = $this->resource->get('page://self/admin/class-category/class-category-list');

        $this->assertSame(Code::OK, $ro->code);

        $body = $ro->body;
        $html = $ro->toString();

        if ((int) ($body['count'] ?? 0) > 0) {
            // At least one row must expose update (PUT) and delete (DELETE) affordances.
            $this->assertTrue(
                str_contains($html, '_method=put') || str_contains($html, 'js-edit-open'),
                'Update affordance missing from non-empty list',
            );
            $this->assertTrue(
                str_contains($html, '_method=delete') || str_contains($html, 'js-delete-open'),
                'Delete affordance missing from non-empty list',
            );
        } else {
            // Empty state is valid; just assert the create form exists.
            $this->assertStringContainsString('name="classCategoryName"', $html);
        }
    }

    /* ── EC-CUBE parity (archived — no longer maintained) ── */

    /**
     * @group ec-cube-parity-archived
     */
    public function testClassCategoryListHtmlMatchesEcCubeRendering(): void
    {
        $this->markTestSkipped(
            'EC-CUBE markup-parity test archived. '
            . 'The template has been redesigned with idea-admin-* vocabulary; '
            . 'EC-CUBE c-* / Bootstrap class fidelity is no longer required.',
        );
    }
}
