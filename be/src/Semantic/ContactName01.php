<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\Name01FormatException;

use function mb_strlen;

/**
 * Contact form family-name — same rules as Customer Name01.
 * Re-uses Name01FormatException so the user-facing message is consistent.
 */
final class ContactName01
{
    #[Validate]
    public function validate(string $contactName01): void
    {
        $length = mb_strlen($contactName01);
        if ($length < 1 || $length > 50) {
            throw new Name01FormatException();
        }
    }
}
