<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query\Result;

use Override;
use Ray\MediaQuery\Result\PostQueryContext;
use Ray\MediaQuery\Result\PostQueryInterface;

final readonly class AllocatedId implements PostQueryInterface
{
    public string $value;

    /** @param string|array<int, self|array<string, mixed>> $nextId */
    public function __construct(string|array $nextId = '1')
    {
        if (is_array($nextId)) {
            $row = $nextId[0] ?? [];
            $nextId = $row instanceof self ? $row->value : (string) (is_array($row) ? ($row['next_id'] ?? $row['value'] ?? '1') : '1');
        }

        $this->value = $nextId;
    }

    #[Override]
    public static function fromContext(PostQueryContext $context): static
    {
        $row = $context->rows[0] ?? [];

        return new static(is_array($row) ? (string) ($row['next_id'] ?? '1') : '1');
    }
}
