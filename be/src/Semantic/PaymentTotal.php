<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\PaymentTotalFormatException;

/**
 * Payment total (支払合計) — amount actually charged to the
 * selected payment method, after points and other adjustments.
 *
 * Non-negative integer (yen). Zero allowed when the order is fully
 * covered by points.
 */
final class PaymentTotal
{
    #[Validate]
    public function validate(int $paymentTotal): void
    {
        if ($paymentTotal < 0) {
            throw new PaymentTotalFormatException();
        }
    }
}
