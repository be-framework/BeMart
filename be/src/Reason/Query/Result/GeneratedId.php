<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query\Result;

use Override;
use Ray\MediaQuery\Result\PostQueryContext;
use Ray\MediaQuery\Result\PostQueryInterface;

final class GeneratedId implements PostQueryInterface
{
    public function __construct(private readonly string $value) {}

    #[Override]
    public static function fromContext(PostQueryContext $context): static
    {
        $row = $context->rows[0] ?? [];
        $value = is_array($row) ? (string) ($row['next_id'] ?? '1') : '1';

        return new static($value);
    }

    public function value(): string
    {
        return $this->value;
    }
}
