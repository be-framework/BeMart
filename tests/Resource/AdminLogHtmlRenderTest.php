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
 * Phase 3 — HTML render checks for GET /admin/log.
 *
 * L1  Required data is present in the rendered output.
 * L2  Actions / links carry correct href / method / rel attributes.
 * L3  Page frame landmarks use the idea-admin-* design vocabulary.
 *
 * EC-CUBE markup parity assertions have been removed; the old
 * c-container / c-headerBar / form#form1 checks are archived below.
 */
final class AdminLogHtmlRenderTest extends TestCase
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

    // ── L1: Required data / fields ────────────────────────────────────────

    /**
     * The resource returns HTTP 200 and the renderer produces a complete
     * HTML document.
     */
    public function testResponseIsOkAndProducesHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/admin/log');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();
        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('</body>', $html);
    }

    /**
     * The filter form renders both required fields: file selector and
     * line-count input.
     */
    public function testFilterFormFieldsArePresent(): void
    {
        $html = $this->resource->get('page://self/admin/log')->toString();

        // file selector (select element rendered by form.input('files'))
        $this->assertStringContainsString('name="files"', $html, 'files select field missing');
        // line_max input
        $this->assertStringContainsString('name="line_max"', $html, 'line_max input field missing');
    }

    // ── L2: Action / link contract ────────────────────────────────────────

    /**
     * The filter form targets GET /admin/log — the only action this
     * read-only resource exposes.
     */
    public function testFilterFormActionIsGetAdminLog(): void
    {
        $html = $this->resource->get('page://self/admin/log')->toString();

        $this->assertMatchesRegularExpression(
            '#<form[^>]+method=["\']get["\'][^>]*action=["\'][^"\']*\/admin\/log["\']#i',
            $html,
            'Filter form must be GET /admin/log',
        );
    }

    // ── L3: Frame landmark (idea-admin design vocabulary) ─────────────────

    /**
     * The shell landmark that wraps all admin pages must be present so
     * that cross-page layout tests can rely on a stable selector.
     */
    public function testAdminShellLandmarkIsPresent(): void
    {
        $html = $this->resource->get('page://self/admin/log')->toString();

        $this->assertStringContainsString(
            'idea-admin-shell',
            $html,
            'idea-admin-shell landmark missing from rendered page',
        );
        $this->assertStringContainsString(
            'idea-admin-content',
            $html,
            'idea-admin-content region missing from rendered page',
        );
    }

    // ── Archived: EC-CUBE parity assertions ───────────────────────────────

    /**
     * @group ec-cube-parity-archived
     * The old test verified EC-CUBE Bootstrap / c-* markup structure.
     * Removed because BeMart uses the idea-admin-* design vocabulary.
     */
    public function testLogPreservesEcCubeAdminMarkupStructure(): void
    {
        $this->markTestSkipped(
            'EC-CUBE markup parity (c-headerBar, c-contentsArea, form#form1) ' .
            'is archived. BeMart uses the idea-admin-* design vocabulary.',
        );
    }
}
