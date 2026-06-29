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
 * Phase 3 — HTML render verification for the admin システム情報 page.
 *
 * L1: required data fields are present in the rendered output.
 * L2: action links and hypermedia structure are correct.
 * Frame: idea-admin-shell landmark is present.
 *
 * EC-CUBE parity assertions have been retired; see @group ec-cube-parity-archived.
 */
final class AdminSystemHtmlRenderTest extends TestCase
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

    // ── L0: HTTP contract ─────────────────────────────────────────────────

    public function testReturnsOkWithHtmlContentType(): void
    {
        $ro = $this->resource->get('page://self/admin/system');

        $this->assertSame(Code::OK, $ro->code);
        $ro->toString();
        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    // ── L1: required data fields present ─────────────────────────────────

    public function testRendersPhpVersionInOutput(): void
    {
        $html = $this->resource->get('page://self/admin/system')->toString();

        $this->assertStringContainsString(PHP_VERSION, $html, 'PHP version must appear in rendered HTML');
    }

    public function testRendersAllInfoRows(): void
    {
        $html = $this->resource->get('page://self/admin/system')->toString();

        foreach (['PHP Version', 'PHP SAPI', 'OS', 'Server', 'Application'] as $label) {
            $this->assertStringContainsString($label, $html, "info row '{$label}' missing from rendered output");
        }
    }

    public function testRendersApplicationName(): void
    {
        $html = $this->resource->get('page://self/admin/system')->toString();

        $this->assertStringContainsString('BeMart', $html, 'Application name BeMart must be present');
    }

    // ── L2: idea-admin frame landmark ─────────────────────────────────────

    public function testRendersIdeaAdminShell(): void
    {
        $html = $this->resource->get('page://self/admin/system')->toString();

        $this->assertStringContainsString('idea-admin-shell', $html, 'idea-admin-shell landmark is required');
        $this->assertStringContainsString('idea-admin-content', $html, 'idea-admin-content region is required');
    }

    public function testRendersPageTitleHeading(): void
    {
        $html = $this->resource->get('page://self/admin/system')->toString();

        $this->assertStringContainsString('idea-admin-page-title', $html, 'page title element is required');
        $this->assertStringContainsString('システム情報', $html, 'page title text must be present');
    }

    // ── L2: phpinfoEnabled=false hides phpinfo section ────────────────────

    public function testPhpinfoSectionHiddenByDefault(): void
    {
        $html = $this->resource->get('page://self/admin/system')->toString();

        $this->assertStringNotContainsString(
            'PHP 詳細情報',
            $html,
            'phpinfo section must not render when phpinfoEnabled is false'
        );
    }

    // ── EC-CUBE parity (retired) ──────────────────────────────────────────

    /**
     * @group ec-cube-parity-archived
     */
    public function testEcCubeMarkupStructureRetired(): void
    {
        $this->markTestSkipped(
            'EC-CUBE markup parity check retired. ' .
            'BeMart uses the idea-admin design language; EC-CUBE Bootstrap/c-* classes are no longer expected.'
        );
    }
}
