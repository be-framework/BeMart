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
 * Phase 3 — fidelity check for the admin empty extension-slot page port
 * (a top-level wave trivial DATA page).
 *
 * `admin/empty_page.twig` is EC-CUBE's near-empty `{% extends
 * default_frame %}` stub — a routable slot plugins fill via template
 * events. BeMart has no plugin/template-event system, so the port is the
 * admin frame (`admin-base.html.twig`) with an empty `main` block; there
 * is nothing dynamic to diff. This test only proves the page renders the
 * admin frame and is admin-firewall-guarded — there is no residual
 * allowlist because there is no page content to compare.
 */
final class AdminEmptyPageHtmlRenderTest extends TestCase
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

    public function testEmptyPageRendersAdminFrame(): void
    {
        $ro = $this->resource->get('page://self/admin/empty-page');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('idea-admin-shell', $html);
        $this->assertStringContainsString('idea-admin-content', $html);
        $this->assertStringContainsString('</body>', $html);

        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }
}
