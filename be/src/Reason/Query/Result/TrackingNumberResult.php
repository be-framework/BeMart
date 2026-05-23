<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query\Result;

use Override;
use Ray\MediaQuery\Result\PostQueryContext;
use Ray\MediaQuery\Result\PostQueryInterface;

final readonly class TrackingNumberResult implements PostQueryInterface
{
    public function __construct(public string|null $trackingNumber) {}

    #[Override]
    public static function fromContext(PostQueryContext $context): static
    {
        $row = $context->rows[0] ?? [];
        $value = is_array($row) && isset($row['tracking_number']) ? (string) $row['tracking_number'] : null;

        return new static($value);
    }
}
