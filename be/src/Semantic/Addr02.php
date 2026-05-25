<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\Addr02FormatException;

use function mb_strlen;

/**
 * Address line 2 — street / building / room (schema.org/streetAddress).
 */
final class Addr02
{
    #[Validate]
    public function validate(string|null $addr02): void
    {
        if ($addr02 === null || $addr02 === '') {
            return;
        }

        if (mb_strlen($addr02) > 100) {
            throw new Addr02FormatException();
        }
    }
}
