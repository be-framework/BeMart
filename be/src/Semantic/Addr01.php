<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\Addr01FormatException;

use function mb_strlen;

/**
 * Address line 1 — city / ward / town (schema.org/addressLocality).
 */
final class Addr01
{
    #[Validate]
    public function validate(string|null $addr01): void
    {
        if ($addr01 === null || $addr01 === '') {
            return;
        }

        if (mb_strlen($addr01) > 50) {
            throw new Addr01FormatException();
        }
    }
}
