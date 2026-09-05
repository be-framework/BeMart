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
 * Phase 3 — clean-room render checks for the admin Page-edit page.
 *
 * Frame landmark assertions use the idea-admin-* vocabulary now that the
 * template has been rebuilt as a first-party design. EC-CUBE DOM-parity
 * assertions are archived in {@see testPageEditHtmlMatchesEcCubeRendering}.
 *
 * L1 — required field / data output presence.
 * L2 — form action/method, back-link href.
 */
final class AdminPageHtmlRenderTest extends TestCase
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

    // ── Frame landmark ────────────────────────────────────────────────────

    public function testPageEditRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/admin/page/page', [
            'pageId' => 'pg-homepage',
        ]);

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('class="idea-admin-shell"', $html);
        $this->assertStringContainsString('class="idea-admin-content"', $html);
        $this->assertStringContainsString('</body>', $html);

        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    // ── L1: required field presence ───────────────────────────────────────

    public function testPageEditRendersRequiredFormFields(): void
    {
        $html = $this->resource->get('page://self/admin/page/page', [
            'pageId' => 'pg-homepage',
        ])->toString();

        // pageName — required
        $this->assertStringContainsString('name="pageName"', $html);
        $this->assertStringContainsString('id="main_edit_name"', $html);
        // Seed value repopulated from resource body
        $this->assertStringContainsString('value="ホームページ"', $html);

        // pageUrl — required
        $this->assertStringContainsString('name="pageUrl"', $html);
        $this->assertStringContainsString('id="main_edit_url"', $html);

        // pageFileName — required
        $this->assertStringContainsString('name="pageFileName"', $html);
        $this->assertStringContainsString('id="main_edit_file_name"', $html);

        // tpl_data — present but disabled (Wave 9 stub)
        $this->assertStringContainsString('id="main_edit_tpl_data"', $html);
        $this->assertStringContainsString('disabled="disabled"', $html);
    }

    public function testPageEditRendersMetaFields(): void
    {
        $html = $this->resource->get('page://self/admin/page/page', [
            'pageId' => 'pg-homepage',
        ])->toString();

        $this->assertStringContainsString('name="author"', $html);
        $this->assertStringContainsString('name="description"', $html);
        $this->assertStringContainsString('name="keyword"', $html);
        $this->assertStringContainsString('name="meta_robots"', $html);
        $this->assertStringContainsString('name="meta_tags"', $html);
    }

    // ── L2: form action / method / navigation links ───────────────────────

    public function testEditFormPostsToPageResourceWithPutOverride(): void
    {
        $html = $this->resource->get('page://self/admin/page/page', [
            'pageId' => 'pg-homepage',
        ])->toString();

        $this->assertStringContainsString(
            'action="/admin/page/page?pageId=pg-homepage&amp;_method=put"',
            $html,
        );
        $this->assertStringContainsString('method="post"', $html);
    }

    public function testNewPageFormPostsToCollectionCreateAction(): void
    {
        $html = $this->resource->get('page://self/admin/page/page')->toString();

        $this->assertStringContainsString('action="/admin/page/page-list"', $html);
        $this->assertStringContainsString('name="pageName"', $html);
        $this->assertStringContainsString('name="pageUrl"', $html);
        $this->assertStringContainsString('name="pageFileName"', $html);
    }

    public function testBackLinkPointsToPageList(): void
    {
        $html = $this->resource->get('page://self/admin/page/page', [
            'pageId' => 'pg-homepage',
        ])->toString();

        $this->assertStringContainsString('href="/admin/page/page-list"', $html);
    }

    public function testCsrfTokenFieldIsPresent(): void
    {
        $html = $this->resource->get('page://self/admin/page/page', [
            'pageId' => 'pg-homepage',
        ])->toString();

        $this->assertStringContainsString('name="csrfToken"', $html);
    }

    // ── EC-CUBE DOM-parity archived ───────────────────────────────────────

    /**
     * The EC-CUBE admin DOM comparison is archived. The clean-room template
     * uses the idea-admin-* design language; DOM structure intentionally
     * diverges from the EC-CUBE reference.
     *
     * @group ec-cube-parity-archived
     */
    public function testPageEditHtmlMatchesEcCubeRendering(): void
    {
        $this->markTestSkipped(
            'EC-CUBE DOM-parity test archived: template rebuilt as clean-room '
            . 'idea-admin design — DOM structure intentionally diverges from EC-CUBE reference.',
        );
    }
}
