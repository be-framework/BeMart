<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\ChargeFormatException;

/**
 * Payment surcharge (手数料) — additional fee applied by the
 * selected payment method (e.g. cash-on-delivery surcharge).
 *
 * Non-negative integer (yen). Zero allowed when no surcharge applies.
 *
 * Wave 7 extension: accepts null so partial-update flows (admin
 * doUpdateOrder) can pass `charge=null` for "do not change this field".
 * Same convention as {@see Pref}, {@see Name01}, etc.
 */
final class Charge
{
    #[Validate]
    public function validate(int|null $charge): void
    {
        if ($charge === null) {
            return;
        }

        if ($charge < 0) {
            throw new ChargeFormatException();
        }
    }
}
