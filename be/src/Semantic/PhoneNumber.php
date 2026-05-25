<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\PhoneNumberFormatException;

use function preg_match;
use function preg_replace;
use function strlen;

/**
 * Phone number — Japanese format. Hyphens optional; the digit count
 * (after stripping hyphens) must be 10 to 13.
 */
final class PhoneNumber
{
    #[Validate]
    public function validate(string|null $phoneNumber): void
    {
        if ($phoneNumber === null || $phoneNumber === '') {
            return;
        }

        if (preg_match('/\A[0-9\-]+\z/', $phoneNumber) !== 1) {
            throw new PhoneNumberFormatException();
        }

        $digits = preg_replace('/-/', '', $phoneNumber);
        $length = strlen((string) $digits);
        if ($length < 10 || $length > 13) {
            throw new PhoneNumberFormatException();
        }
    }
}
