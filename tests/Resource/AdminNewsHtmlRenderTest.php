<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Form\AdminNewsForm;
use MyVendor\BeMart\Tests\Support\HtmlTestInjector;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;

use function preg_replace;
use function str_contains;
use function trim;

/**
 * Markup-parity verification for the admin News-edit page (idea-admin rebuild).
 *
 * L1 — required field presence and list data output.
 * L2 — form action/method routing and link href/rel.
 *
 * EC-CUBE reference-rendering tests are archived under
 * {@see Group('ec-cube-parity-archived')} and skipped unconditionally.
 */
final class AdminNewsHtmlRenderTest extends TestCase
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

    // ── L0: HTML document structure ─────────────────────────────────────────

    public function testNewsEditRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/admin/news/news', [
            'newsId' => 'nw-welcome',
        ]);

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('<div class="idea-admin-shell">', $html);
        $this->assertStringContainsString('</body>', $html);

        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    public function testNewsEditUsesIdeaAdminShellLandmarks(): void
    {
        $html = $this->resource->get('page://self/admin/news/news', [
            'newsId' => 'nw-welcome',
        ])->toString();

        foreach ([
            '<div class="idea-admin-shell">',
            'class="idea-admin-topbar"',
            'class="idea-admin-sidebar"',
            'class="idea-admin-content"',
        ] as $landmark) {
            $this->assertStringContainsString($landmark, $html, "idea-admin landmark missing: {$landmark}");
        }
    }

    // ── L1: Required field presence ─────────────────────────────────────────

    /**
     * The form must render all five AdminNewsForm fields.
     */
    public function testNewsEditRendersRealFormInputs(): void
    {
        $html = $this->resource->get('page://self/admin/news/news', [
            'newsId' => 'nw-welcome',
        ])->toString();

        // Form routing — & is HTML-escaped to &amp; by Twig
        $this->assertStringContainsString('action="/admin/news/news?newsId=nw-welcome&amp;_method=put"', $html);

        // publishDate field
        $this->assertStringContainsString('id="admin_news_publish_date"', $html);
        $this->assertStringContainsString('name="publishDate"', $html);

        // newsTitle field pre-filled with seed value
        $this->assertStringContainsString('id="admin_news_title"', $html);
        $this->assertStringContainsString('name="newsTitle"', $html);
        $this->assertStringContainsString('value="ようこそ"', $html);

        // newsUrl field
        $this->assertStringContainsString('id="admin_news_url"', $html);
        $this->assertStringContainsString('name="newsUrl"', $html);

        // linkMethod checkbox — hidden false-value sentinel + checkbox
        $this->assertStringContainsString('name="linkMethod" value="0"', $html);
        $this->assertStringContainsString('id="admin_news_link_method"', $html);
        $this->assertStringContainsString('name="linkMethod" value="1"', $html);
        $this->assertStringNotContainsString('name="linkMethod[]"', $html);

        // newsDescription textarea
        $this->assertStringContainsString('<textarea id="admin_news_description"', $html);
        $this->assertStringContainsString('name="newsDescription"', $html);
    }

    /**
     * Required field labels carry the idea-admin-required badge.
     */
    public function testRequiredFieldsBadged(): void
    {
        $html = $this->resource->get('page://self/admin/news/news', [
            'newsId' => 'nw-welcome',
        ])->toString();

        $this->assertStringContainsString('class="idea-admin-required"', $html);
    }

    // ── L2: Form action/method routing and back link ─────────────────────────

    /**
     * New-news form (no newsId) posts to the collection create endpoint.
     */
    public function testNewNewsFormPostsToCollectionCreateAction(): void
    {
        $html = $this->resource->get('page://self/admin/news/news')->toString();

        $this->assertStringContainsString('action="/admin/news/news-list"', $html);
        $this->assertStringContainsString('name="newsTitle"', $html);
        $this->assertStringContainsString('name="publishDate"', $html);
        $this->assertStringContainsString('name="newsUrl"', $html);
        $this->assertStringContainsString('name="newsDescription"', $html);
    }

    /**
     * Edit page includes a separate delete form routed via _method=delete.
     */
    public function testDeleteFormPresentInEditMode(): void
    {
        $html = $this->resource->get('page://self/admin/news/news', [
            'newsId' => 'nw-welcome',
        ])->toString();

        $this->assertStringContainsString('id="news-delete-form"', $html);
        $this->assertStringContainsString('_method=delete', $html);
    }

    /**
     * Back link navigates to the news list (goNewsList rel).
     */
    public function testBackLinkPointsToNewsList(): void
    {
        $html = $this->resource->get('page://self/admin/news/news', [
            'newsId' => 'nw-welcome',
        ])->toString();

        $this->assertStringContainsString('href="/admin/news/news-list"', $html);
    }

    /**
     * CSRF token hidden input is emitted.
     */
    public function testCsrfTokenHiddenInputPresent(): void
    {
        $html = $this->resource->get('page://self/admin/news/news', [
            'newsId' => 'nw-welcome',
        ])->toString();

        $this->assertStringContainsString('name="csrfToken"', $html);
    }

    // ── Archived: EC-CUBE reference-rendering tests ──────────────────────────

    /**
     * @deprecated EC-CUBE 4.3 reference rendering comparison.
     * Archived: the clean-room rebuild no longer targets EC-CUBE DOM parity.
     * Retained as a skipped placeholder for historical audit trail.
     */
    #[Group('ec-cube-parity-archived')]
    public function testNewsEditHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $this->markTestSkipped(
            'EC-CUBE DOM-parity test archived: the News edit page has been ' .
            'rebuilt as a clean-room idea-admin template and no longer ' .
            'targets EC-CUBE landmark or class-name parity.'
        );
    }

    /**
     * @deprecated EC-CUBE admin markup structure check.
     * Archived: c-* / Bootstrap landmarks replaced by idea-admin-* equivalents.
     */
    #[Group('ec-cube-parity-archived')]
    public function testNewsEditPreservesEcCubeAdminMarkupStructure(): void
    {
        $this->markTestSkipped(
            'EC-CUBE admin markup structure test archived: c-container / ' .
            'c-mainNavArea / c-headerBar landmarks have been superseded by ' .
            'idea-admin-shell / idea-admin-content / idea-admin-topbar.'
        );
    }
}
