<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query\Result;

use Override;
use Ray\MediaQuery\Result\PostQueryContext;
use Ray\MediaQuery\Result\PostQueryInterface;

final readonly class ProductStatusUpdate implements PostQueryInterface
{
    public function __construct(public int $changedCount) {}

    #[Override]
    public static function fromContext(PostQueryContext $context): static
    {
        $row = $context->rows[0] ?? [];
        $changedCount = is_array($row) ? (int) ($row['changed_count'] ?? 0) : $context->statement->rowCount();

        return new static($changedCount);
    }
}
