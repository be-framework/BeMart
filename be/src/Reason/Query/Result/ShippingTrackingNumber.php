<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query\Result;

use Override;
use Ray\MediaQuery\Result\PostQueryContext;
use Ray\MediaQuery\Result\PostQueryInterface;

final readonly class ShippingTrackingNumber implements PostQueryInterface
{
    public string|null $trackingNumber;

    /** @param string|null|array<int, self|array<string, mixed>> $trackingNumber */
    public function __construct(string|null|array $trackingNumber)
    {
        if (is_array($trackingNumber)) {
            $row = $trackingNumber[0] ?? null;
            $trackingNumber = $row instanceof self ? $row->trackingNumber : (is_array($row) && isset($row['tracking_number']) ? (string) $row['tracking_number'] : null);
        }

        $this->trackingNumber = $trackingNumber;
    }

    #[Override]
    public static function fromContext(PostQueryContext $context): static
    {
        $row = $context->rows[0] ?? [];
        $value = is_array($row) && isset($row['tracking_number']) ? (string) $row['tracking_number'] : null;

        return new static($value);
    }
}
