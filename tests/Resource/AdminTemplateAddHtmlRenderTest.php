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
 * Phase 3 — HTML render check for the admin テンプレート登録 Store
 * Tier-2 page (`admin/Store/template_add.twig`).
 *
 * Render-smoke standard: the page renders a full admin-frame HTML
 * document through {@see HtmlModule} with the body shape supplied by
 * {@see \MyVendor\BeMart\Resource\Page\Admin\Template\TemplateAdd}. The
 * EC-CUBE residual-diff fidelity check is a follow-up gated on the
 * EC-CUBE 4.3 reference clone (`tools/ec-cube-source/`, currently
 * absent).
 */
final class AdminTemplateAddHtmlRenderTest extends TestCase
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

    public function testTemplateAddRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/admin/template/template-add');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('<div class="c-container">', $html);
        $this->assertStringContainsString('</body>', $html);
        $this->assertStringContainsString('<header class="c-headerBar">', $html);
        $this->assertStringContainsString('<div class="c-contentsArea">', $html);
        $this->assertStringContainsString('id="template_add_form"', $html);
        $this->assertStringContainsString('テンプレート登録', $html);

        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }
}
