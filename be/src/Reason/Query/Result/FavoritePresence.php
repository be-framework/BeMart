<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query\Result;

use Override;
use Ray\MediaQuery\Result\PostQueryContext;
use Ray\MediaQuery\Result\PostQueryInterface;

final readonly class FavoritePresence implements PostQueryInterface
{
    public bool $exists;

    /** @param bool|array<int, self|array<string, mixed>> $exists */
    public function __construct(bool|array $exists)
    {
        if (is_array($exists)) {
            $row = $exists[0] ?? null;
            $exists = $row instanceof self ? $row->exists : $exists !== [];
        }

        $this->exists = $exists;
    }

    #[Override]
    public static function fromContext(PostQueryContext $context): static
    {
        return new static($context->rows !== []);
    }
}
