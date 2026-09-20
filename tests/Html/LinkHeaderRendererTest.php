<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Html;

use BEAR\Resource\NullReverseLinker;
use BEAR\Resource\RenderInterface;
use BEAR\Resource\ReverseLinkerInterface;
use BEAR\Resource\Uri;
use MyVendor\BeMart\Support\Html\AuditedLinkHeaderModule;
use MyVendor\BeMart\Support\Html\AuditedLinkHeaderRenderer;
use MyVendor\BeMart\Support\Html\HtmlLinkAuditLoggerInterface;
use MyVendor\BeMart\Support\Html\HtmlLinkAuditor;
use MyVendor\BeMart\Support\Html\LinkHeaderModule;
use MyVendor\BeMart\Support\Html\LinkHeaderRenderer;
use MyVendor\BeMart\Tests\Fake\Html\LinkedResourceObject;
use MyVendor\BeMart\Tests\Fake\Html\NonSemanticAnchorRenderer;
use MyVendor\BeMart\Tests\Fake\Html\SemanticAnchorRenderer;
use MyVendor\BeMart\Tests\Support\RecordingHtmlLinkAuditLogger;
use Override;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

final class LinkHeaderRendererTest extends TestCase
{
    public function testRenderAddsLinkHeader(): void
    {
        $renderer = new LinkHeaderRenderer(
            new SemanticAnchorRenderer(),
            new NullReverseLinker(),
        );
        $ro = new LinkedResourceObject();
        $ro->uri = new Uri('page://self/current');
        $ro->uri->method = 'get';
        $ro->body = [];

        $view = $renderer->render($ro);

        $this->assertSame('<a href="/next" class="goNext">Next</a>', $view);
        $this->assertSame('</next>; rel="goNext"; method="get"', $ro->headers['Link']);
    }

    public function testAuditedRendererAddsLinkHeaderAndAuditsRenderedHtml(): void
    {
        $logger = new RecordingHtmlLinkAuditLogger();
        $renderer = new AuditedLinkHeaderRenderer(
            new LinkHeaderRenderer(
                new SemanticAnchorRenderer(),
                new NullReverseLinker(),
            ),
            new HtmlLinkAuditor($logger),
        );
        $ro = new LinkedResourceObject();
        $ro->uri = new Uri('page://self/current');
        $ro->uri->method = 'get';
        $ro->body = [];

        $view = $renderer->render($ro);

        $this->assertSame('<a href="/next" class="goNext">Next</a>', $view);
        $this->assertSame('</next>; rel="goNext"; method="get"', $ro->headers['Link']);
        $this->assertSame([], $logger->drain(), 'the rendered anchor already carries the rel; nothing should be flagged');
    }

    public function testAuditedRendererFlagsAnAnchorMissingItsSemanticToken(): void
    {
        $logger = new RecordingHtmlLinkAuditLogger();
        $renderer = new AuditedLinkHeaderRenderer(
            new LinkHeaderRenderer(
                new NonSemanticAnchorRenderer(),
                new NullReverseLinker(),
            ),
            new HtmlLinkAuditor($logger),
        );
        $ro = new LinkedResourceObject();
        $ro->uri = new Uri('page://self/current');
        $ro->uri->method = 'get';
        $ro->body = [];

        $renderer->render($ro);

        $warnings = $logger->drain();
        $this->assertSame('goNext', $warnings[0]['rel'] ?? null);
        $this->assertSame('semantic-token-missing', $warnings[0]['reason'] ?? null);
    }

    public function testModuleRenamesPreviousRenderer(): void
    {
        $injector = new Injector(new LinkHeaderModule(new class extends AbstractModule {
            #[Override]
            protected function configure(): void
            {
                $this->bind(RenderInterface::class)->to(SemanticAnchorRenderer::class);
                $this->bind(ReverseLinkerInterface::class)->to(NullReverseLinker::class);
            }
        }));
        $renderer = $injector->getInstance(RenderInterface::class);

        $this->assertInstanceOf(LinkHeaderRenderer::class, $renderer);
    }

    public function testAuditedModuleWrapsLinkHeaderModuleAndAudits(): void
    {
        $logger = new RecordingHtmlLinkAuditLogger();
        $base = new AuditedLinkHeaderModule(new LinkHeaderModule(new class extends AbstractModule {
            #[Override]
            protected function configure(): void
            {
                $this->bind(RenderInterface::class)->to(NonSemanticAnchorRenderer::class);
                $this->bind(ReverseLinkerInterface::class)->to(NullReverseLinker::class);
            }
        }));
        $base->override(new class ($logger) extends AbstractModule {
            public function __construct(private readonly RecordingHtmlLinkAuditLogger $logger)
            {
                parent::__construct();
            }

            #[Override]
            protected function configure(): void
            {
                $this->bind(HtmlLinkAuditLoggerInterface::class)->toInstance($this->logger);
            }
        });
        $injector = new Injector($base);
        $renderer = $injector->getInstance(RenderInterface::class);
        $this->assertInstanceOf(AuditedLinkHeaderRenderer::class, $renderer);

        $ro = new LinkedResourceObject();
        $ro->uri = new Uri('page://self/current');
        $ro->uri->method = 'get';
        $ro->body = [];

        $renderer->render($ro);

        $warnings = $logger->drain();
        $this->assertSame('goNext', $warnings[0]['rel'] ?? null, 'the module wiring must actually reach the auditor, not just resolve to the audited class');
        $this->assertSame('semantic-token-missing', $warnings[0]['reason'] ?? null);
    }
}
