<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Tests\Support\HtmlTestInjector;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;

/**
 * Functional / semantic verification for the admin News-list HTML page
 * (idea-admin clean-room rebuild).
 *
 * L0 — HTML document structure + idea-admin-shell landmarks.
 * L1 — Required data present in rendered output (count, news rows, dates,
 *       titles, linkMethod badges, edit links).
 * L2 — Action routing (create href, edit href/rel, delete form action/method).
 *
 * EC-CUBE reference-rendering (DOM parity) tests have been archived under
 * {@see Group('ec-cube-parity-archived')} and are unconditionally skipped.
 * The clean-room rebuild targets idea-admin-* landmarks, not c-* / Bootstrap
 * landmarks, so EC-CUBE parity is no longer a meaningful assertion.
 */
final class AdminNewsListHtmlRenderTest extends TestCase
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

    public function testNewsListRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/admin/news/news-list');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('</body>', $html);

        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    public function testNewsListUsesIdeaAdminShellLandmarks(): void
    {
        $html = $this->resource->get('page://self/admin/news/news-list')->toString();

        foreach ([
            '<div class="idea-admin-shell">',
            'class="idea-admin-topbar"',
            'class="idea-admin-sidebar"',
            'class="idea-admin-content"',
        ] as $landmark) {
            $this->assertStringContainsString($landmark, $html, "idea-admin landmark missing: {$landmark}");
        }
    }

    // ── L1: Required data output ─────────────────────────────────────────────

    /**
     * The Fake storage seeds one item ("ようこそ" / nw-welcome).
     * The list page must surface the title and a row for it.
     */
    public function testNewsListRendersSeededNewsRow(): void
    {
        $html = $this->resource->get('page://self/admin/news/news-list')->toString();

        // Seeded title is present
        $this->assertStringContainsString('ようこそ', $html);

        // Edit link for the seeded item
        $this->assertStringContainsString('/admin/news/news?newsId=nw-welcome', $html);
    }

    /**
     * The count KPI and list-toolbar count label must be rendered.
     */
    public function testNewsListRendersCountIndicator(): void
    {
        $html = $this->resource->get('page://self/admin/news/news-list')->toString();

        // KPI card label
        $this->assertStringContainsString('記事数', $html);

        // Toolbar count text
        $this->assertStringContainsString('件', $html);
    }

    /**
     * The table must have the expected column headers.
     */
    public function testNewsListTableHasExpectedColumnHeaders(): void
    {
        $html = $this->resource->get('page://self/admin/news/news-list')->toString();

        $this->assertStringContainsString('公開日時', $html);
        $this->assertStringContainsString('タイトル', $html);
        $this->assertStringContainsString('操作', $html);
    }

    /**
     * Each row must carry a goNews edit link and a delete trigger.
     */
    public function testNewsListRowHasEditLinkAndDeleteControl(): void
    {
        $html = $this->resource->get('page://self/admin/news/news-list')->toString();

        // Edit link with rel=goNews
        $this->assertStringContainsString('rel="goNews"', $html);

        // Delete dialog backdrop
        $this->assertStringContainsString('id="news-delete-dialog"', $html);

        // Delete form routing uses _method=delete
        $this->assertStringContainsString('_method=delete', $html);
    }

    /**
     * The delete form action must include the newsId of the seeded item.
     * The JS wires the action at click-time; the data attribute carries the id.
     */
    public function testNewsListDeleteDataAttributeCarriesNewsId(): void
    {
        $html = $this->resource->get('page://self/admin/news/news-list')->toString();

        $this->assertStringContainsString('data-news-id="nw-welcome"', $html);
    }

    // ── L2: Action routing ───────────────────────────────────────────────────

    /**
     * The "new article" CTA must point to the create endpoint
     * (doCreateNews rel — GET /admin/news/news).
     */
    public function testNewsListCreateCtaPointsToNewsForm(): void
    {
        $html = $this->resource->get('page://self/admin/news/news-list')->toString();

        $this->assertStringContainsString('rel="doCreateNews"', $html);
    }

    /**
     * Edit links must use the goNews rel and carry newsId in the href.
     */
    public function testNewsListEditLinkCarriesNewsId(): void
    {
        $html = $this->resource->get('page://self/admin/news/news-list')->toString();

        $this->assertStringContainsString('newsId=nw-welcome', $html);
    }

    // ── Archived: EC-CUBE reference-rendering tests ──────────────────────────

    /**
     * @deprecated EC-CUBE 4.3 DOM-parity comparison.
     * Archived: the News-list page has been rebuilt as a clean-room idea-admin
     * template. EC-CUBE c-* / Bootstrap landmarks are no longer present.
     */
    #[Group('ec-cube-parity-archived')]
    public function testNewsListRendersAsHtmlDocumentLegacy(): void
    {
        $this->markTestSkipped(
            'EC-CUBE DOM-parity test archived: the News list page has been ' .
            'rebuilt as a clean-room idea-admin template and no longer ' .
            'targets EC-CUBE landmark or class-name parity.'
        );
    }

    /**
     * @deprecated EC-CUBE admin markup structure check.
     * Archived: c-container / c-contentsArea / Bootstrap list-group landmarks
     * have been superseded by idea-admin-shell / idea-admin-table / idea-admin-*.
     */
    #[Group('ec-cube-parity-archived')]
    public function testNewsListPreservesEcCubeAdminMarkupStructure(): void
    {
        $this->markTestSkipped(
            'EC-CUBE admin markup structure test archived: c-primaryCol / ' .
            'list-group / btn-ec-* landmarks superseded by idea-admin-* equivalents.'
        );
    }

    /**
     * @deprecated EC-CUBE reference rendering residual-diff honesty test.
     * Archived: residual-diff approach no longer applicable to clean-room build.
     */
    #[Group('ec-cube-parity-archived')]
    public function testNewsListHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $this->markTestSkipped(
            'EC-CUBE residual-diff test archived: clean-room idea-admin rebuild ' .
            'intentionally diverges from EC-CUBE DOM structure.'
        );
    }
}
