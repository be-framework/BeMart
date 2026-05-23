<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query\Result;

use Override;
use Ray\MediaQuery\Result\PostQueryContext;
use Ray\MediaQuery\Result\PostQueryInterface;

final class TrackingNumberResult implements PostQueryInterface
{
    public function __construct(private readonly string|null $value) {}

    #[Override]
    public static function fromContext(PostQueryContext $context): static
    {
        $row = $context->rows[0] ?? [];
        $value = is_array($row) && isset($row['tracking_number']) ? (string) $row['tracking_number'] : null;

        return new static($value);
    }

    public function valueOrNull(): string|null
    {
        return $this->value;
    }
}
