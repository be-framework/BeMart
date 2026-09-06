<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Fake\Html;

use BEAR\Resource\RenderInterface;
use BEAR\Resource\ResourceObject;
use Override;

final class SemanticAnchorRenderer implements RenderInterface
{
    #[Override]
    public function render(ResourceObject $ro): string
    {
        unset($ro);

        return '<a href="/next" class="goNext">Next</a>';
    }
}
