<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Fake\Html;

use BEAR\Resource\RenderInterface;
use BEAR\Resource\ResourceObject;
use Override;

/**
 * Renders the same affordance as {@see SemanticAnchorRenderer} but without the `rel`/`class`
 * token a `#[Link]` declares - the defect {@see \MyVendor\BeMart\Support\Html\HtmlLinkAuditor}
 * exists to catch. Used to prove the audit actually flags a real gap, not just that it stays
 * quiet when nothing is wrong.
 */
final class NonSemanticAnchorRenderer implements RenderInterface
{
    #[Override]
    public function render(ResourceObject $ro): string
    {
        unset($ro);

        return '<a href="/next">Next</a>';
    }
}
