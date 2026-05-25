<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query\Result;

use Override;
use Ray\MediaQuery\Result\PostQueryContext;
use Ray\MediaQuery\Result\PostQueryInterface;

final readonly class ProductStatusUpdate implements PostQueryInterface
{
    public int $changedCount;

    /** @param int|array<int, self|array<string, mixed>> $changedCount */
    public function __construct(int|array $changedCount)
    {
        if (is_array($changedCount)) {
            $row = $changedCount[0] ?? [];
            $changedCount = $row instanceof self ? $row->changedCount : (int) (is_array($row) ? ($row['changed_count'] ?? $row['changedCount'] ?? 0) : 0);
        }

        $this->changedCount = $changedCount;
    }

    #[Override]
    public static function fromContext(PostQueryContext $context): static
    {
        $row = $context->rows[0] ?? [];
        $changedCount = is_array($row) ? (int) ($row['changed_count'] ?? 0) : $context->statement->rowCount();

        return new static($changedCount);
    }
}
