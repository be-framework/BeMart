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
 * Phase 3 — HTML render check for the admin holiday calendar page.
 *
 * L1: structural/semantic correctness — the page renders a valid
 *     admin-frame HTML document and contains the required form fields
 *     and list columns for the holiday calendar feature.
 *
 * L2: hypermedia contracts — form action/method and delete href are
 *     derived from the #[Link] annotations on Calendar resource.
 *
 * EC-CUBE 4.3 pixel-diff rendering (previously testCalendarPreservesEcCubeAdminMarkupStructure)
 * is archived below as it compares against EC-CUBE admin markup that has been
 * replaced by the idea-admin cleanroom template.
 */
final class AdminCalendarHtmlRenderTest extends TestCase
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

    // ── L1: structural smoke ─────────────────────────────────────────────

    /**
     * The admin calendar page renders as a complete HTML document
     * served through the HtmlModule.
     */
    public function testCalendarRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/admin/calendar');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('</body>', $html);

        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    /**
     * The admin shell landmarks from admin-base.html.twig are present.
     */
    public function testAdminShellLandmarksPresent(): void
    {
        $html = $this->resource->get('page://self/admin/calendar')->toString();

        $this->assertStringContainsString('class="idea-admin-shell"', $html);
        $this->assertStringContainsString('class="idea-admin-content"', $html);
    }

    // ── L1: required field output ─────────────────────────────────────────

    /**
     * The create form renders the `title` and `holiday` input fields
     * (AdminCalendarForm — both are required).
     */
    public function testCreateFormRendersRequiredFields(): void
    {
        $html = $this->resource->get('page://self/admin/calendar')->toString();

        // The form fields are rendered by form.input('title')|raw and form.input('holiday')|raw.
        // AdminCalendarForm sets id="calendar_title" and id="calendar_holiday".
        $this->assertStringContainsString('id="calendar_title"', $html);
        $this->assertStringContainsString('id="calendar_holiday"', $html);
    }

    /**
     * The holiday list table is present and uses the idea-admin-table class.
     */
    public function testHolidayListTableIsRendered(): void
    {
        $html = $this->resource->get('page://self/admin/calendar')->toString();

        // Either the populated table or the empty-state panel is rendered.
        $hasTable = str_contains($html, 'idea-admin-table');
        $hasEmpty = str_contains($html, 'idea-admin-empty');

        $this->assertTrue(
            $hasTable || $hasEmpty,
            'Expected either idea-admin-table (populated) or idea-admin-empty (zero results)',
        );
    }

    /**
     * The create-form container carries the id that downstream JS and
     * integration tests use as the new-entry anchor.
     */
    public function testCreateFormContainerIdPresent(): void
    {
        $html = $this->resource->get('page://self/admin/calendar')->toString();

        $this->assertStringContainsString('id="calendar_item_new"', $html);
    }

    // ── L2: hypermedia contracts ──────────────────────────────────────────

    /**
     * The create form posts to the doCreateCalendarHoliday endpoint
     * derived from #[Link(rel: 'doCreateCalendarHoliday', href: 'page://self/admin/calendar', method: 'post')].
     */
    public function testCreateFormActionAndMethod(): void
    {
        $html = $this->resource->get('page://self/admin/calendar')->toString();

        $this->assertStringContainsString('action="/admin/calendar?operation=create"', $html);
        // The form element carries method="post" (CSRF-protected mutation).
        $this->assertMatchesRegularExpression(
            '/<form[^>]+id="calendar_item_new"[^>]*method="post"/s',
            $html,
        );
    }

    /**
     * The delete affordance href encodes the doDeleteCalendarHoliday endpoint.
     * #[Link(rel: 'doDeleteCalendarHoliday', href: 'page://self/admin/calendar', method: 'delete')]
     */
    public function testDeleteAffordanceHref(): void
    {
        $html = $this->resource->get('page://self/admin/calendar')->toString();

        // The delete form uses _method=delete tunneling (BEAR.Sunday convention).
        // Only assert the pattern when the list is populated.
        if (! str_contains($html, 'idea-admin-table')) {
            $this->markTestSkipped('No holiday rows present in fake data; delete affordance not rendered.');
        }

        $this->assertStringContainsString('_method=delete', $html);
        $this->assertStringContainsString('/admin/calendar?calendarId=', $html);
    }

    /**
     * The CSRF token field is present in the create form
     * (resource body carries csrfToken from CsrfToken service).
     */
    public function testCsrfTokenFieldPresent(): void
    {
        $html = $this->resource->get('page://self/admin/calendar')->toString();

        $this->assertStringContainsString('name="csrfToken"', $html);
    }

    // ── Archived EC-CUBE parity tests ─────────────────────────────────────

    /**
     * @group ec-cube-parity-archived
     *
     * This test compared the rendered output against EC-CUBE 4.3 admin
     * markup landmarks (c-headerBar, c-contentsArea, etc.). The Calendar
     * template has been rebuilt using the idea-admin cleanroom design
     * language; EC-CUBE-derived class names are no longer present.
     *
     * Re-enable and adapt if a pixel-level EC-CUBE parity gate is reinstated
     * against a live EC-CUBE 4.3 reference clone in tools/ec-cube-source/.
     */
    public function testCalendarPreservesEcCubeAdminMarkupStructure(): void
    {
        $this->markTestSkipped(
            'EC-CUBE admin markup landmarks (c-headerBar, c-contentsArea, etc.) '
            . 'are not present in the idea-admin cleanroom template. '
            . 'Archived as @group ec-cube-parity-archived.',
        );
    }
}
