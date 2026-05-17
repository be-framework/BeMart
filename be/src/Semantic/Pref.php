<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\PrefFormatException;

/**
 * Prefecture id — 1 (Hokkaido) … 47 (Okinawa) per EC-CUBE's mtb_pref.
 */
final class Pref
{
    #[Validate]
    public function validate(int|null $pref): void
    {
        if ($pref === null) {
            return;
        }

        if ($pref < 1 || $pref > 47) {
            throw new PrefFormatException();
        }
    }
}
