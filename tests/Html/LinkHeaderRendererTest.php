<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Html;

use BEAR\Resource\NullReverseLinker;
use BEAR\Resource\RenderInterface;
use BEAR\Resource\ReverseLinkerInterface;
use BEAR\Resource\Uri;
use MyVendor\BeMart\Support\Html\HtmlLinkAuditLoggerInterface;
use MyVendor\BeMart\Support\Html\HtmlLinkAuditor;
use MyVendor\BeMart\Support\Html\LinkHeaderModule;
use MyVendor\BeMart\Support\Html\LinkHeaderRenderer;
use MyVendor\BeMart\Tests\Fake\Html\LinkedResourceObject;
use MyVendor\BeMart\Tests\Fake\Html\SemanticAnchorRenderer;
use MyVendor\BeMart\Tests\Support\RecordingHtmlLinkAuditLogger;
use Override;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

final class LinkHeaderRendererTest extends TestCase
{
    public function testRenderAddsLinkHeaderAndAuditsRenderedHtml(): void
    {
        $logger = new RecordingHtmlLinkAuditLogger();
        $renderer = new LinkHeaderRenderer(
            new SemanticAnchorRenderer(),
            new NullReverseLinker(),
            new HtmlLinkAuditor($logger),
        );
        $ro = new LinkedResourceObject();
        $ro->uri = new Uri('page://self/current');
        $ro->uri->method = 'get';
        $ro->body = [];

        $view = $renderer->render($ro);

        $this->assertSame('<a href="/next" class="goNext">Next</a>', $view);
        $this->assertSame('</next>; rel="goNext"; method="get"', $ro->headers['Link']);
        $this->assertSame([], $logger->drain());
    }

    public function testModuleRenamesPreviousRenderer(): void
    {
        $injector = new Injector(new LinkHeaderModule(new class extends AbstractModule {
            #[Override]
            protected function configure(): void
            {
                $this->bind(RenderInterface::class)->to(SemanticAnchorRenderer::class);
                $this->bind(ReverseLinkerInterface::class)->to(NullReverseLinker::class);
                $this->bind(HtmlLinkAuditLoggerInterface::class)->toInstance(new RecordingHtmlLinkAuditLogger());
            }
        }));
        $renderer = $injector->getInstance(RenderInterface::class);

        $this->assertInstanceOf(LinkHeaderRenderer::class, $renderer);
    }
}
