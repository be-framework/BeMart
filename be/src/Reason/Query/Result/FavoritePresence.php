<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query\Result;

use Override;
use Ray\MediaQuery\Result\PostQueryContext;
use Ray\MediaQuery\Result\PostQueryInterface;

final class FavoritePresence implements PostQueryInterface
{
    public function __construct(private readonly bool $exists) {}

    #[Override]
    public static function fromContext(PostQueryContext $context): static
    {
        return new static($context->rows !== []);
    }

    public function exists(): bool
    {
        return $this->exists;
    }
}
