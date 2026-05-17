<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\PostalCodeFormatException;

use function preg_match;

/**
 * Postal code — Japanese format. 7 digits, with optional hyphen
 * between the third and fourth digit.
 */
final class PostalCode
{
    #[Validate]
    public function validate(string|null $postalCode): void
    {
        if ($postalCode === null || $postalCode === '') {
            return;
        }

        if (preg_match('/\A\d{7}\z|\A\d{3}-\d{4}\z/', $postalCode) !== 1) {
            throw new PostalCodeFormatException();
        }
    }
}
