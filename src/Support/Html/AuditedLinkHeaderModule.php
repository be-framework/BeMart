<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Support\Html;

use BEAR\Resource\RenderInterface;
use Override;
use Ray\Di\AbstractModule;
use Ray\Di\Scope;

/**
 * Test-only: swaps the Link-header renderer a graph already resolved for
 * {@see AuditedLinkHeaderRenderer}, so a test that needs the html-link-audit result can ask for
 * it explicitly instead of it running unconditionally in every context. See #132.
 *
 * `install()` this module (or pass it to {@see \Ray\Di\Injector}) after whatever composed
 * {@see LinkHeaderModule} - its own bind() calls win over anything merged in afterward, per
 * {@see AbstractModule}'s "own bindings take priority over the chained module's" contract, so it
 * does not need to be the module {@see LinkHeaderModule} itself was built from.
 *
 * Never bound by a production context - {@see \MyVendor\BeMart\Module\HtmlModule} composes plain
 * {@see LinkHeaderModule}, which never mentions {@see HtmlLinkAuditor} at all.
 */
final class AuditedLinkHeaderModule extends AbstractModule
{
    #[Override]
    protected function configure(): void
    {
        $this->bind(RenderInterface::class)->to(AuditedLinkHeaderRenderer::class)->in(Scope::SINGLETON);
        $this->bind(LinkHeaderRenderer::class);
        $this->bind(HtmlLinkAuditor::class);
        $this->bind(HtmlLinkAuditLoggerInterface::class)->to(SilentHtmlLinkAuditLogger::class);
    }
}
