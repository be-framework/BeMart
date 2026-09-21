<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Support\Html;

use BEAR\Resource\RenderInterface;
use BEAR\Resource\ResourceObject;
use Override;

/**
 * The production Link-header contract, plus the html-link-audit regex scan production never
 * pays for.
 *
 * {@see HtmlLinkAuditor} decides nothing on its own render path in a production context - see
 * #132. This decorator exists so a test (the html-link-audit ledger, this renderer's own
 * contract test) can ask for the audited behaviour explicitly, via {@see AuditedLinkHeaderModule},
 * without the cost or the dependency living in {@see LinkHeaderRenderer} itself.
 */
final class AuditedLinkHeaderRenderer implements RenderInterface
{
    public function __construct(
        private readonly LinkHeaderRenderer $renderer,
        private readonly HtmlLinkAuditor $auditor,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function render(ResourceObject $ro)
    {
        $links = $this->renderer->links($ro);
        $view = $this->renderer->render($ro);
        $this->auditor->audit($links, $view);

        return $view;
    }
}
