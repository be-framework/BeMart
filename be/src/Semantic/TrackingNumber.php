<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\TrackingNumberFormatException;

use function mb_strlen;

/**
 * Carrier package tracking number (荷物追跡番号) — dtb_shipping's
 * `tracking_number` column. Combined with the carrier's confirmUrl it
 * forms the customer-facing tracking link.
 *
 * Carriers use varied formats (digits, with or without hyphens), so the
 * validation is deliberately loose: a non-empty string within the
 * `varchar(255)` column bound. An empty string is rejected — clearing a
 * tracking number is not what `doUpdateTrackingNumber` is for.
 */
final class TrackingNumber
{
    #[Validate]
    public function validate(string|null $trackingNumber): void
    {
        if ($trackingNumber === null) {
            return;
        }

        $length = mb_strlen($trackingNumber);
        if ($length < 1 || $length > 255) {
            throw new TrackingNumberFormatException();
        }
    }
}
