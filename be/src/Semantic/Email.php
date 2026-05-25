<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\EmailFormatException;

use function mb_strlen;
use function str_contains;

/**
 * Email — EC-CUBE 4.3 dtb_customer.email. Doubles as the customer's
 * login id (unique among active customers; dynamic uniqueness is
 * enforced by EmailUniquenessCheckerInterface, not by this Semantic).
 *
 * Static constraints only — RFC 5322 contains-`@` + length cap 254.
 */
final class Email
{
    #[Validate]
    public function validate(string $email): void
    {
        if (! str_contains($email, '@') || mb_strlen($email) > 254 || mb_strlen($email) < 3) {
            throw new EmailFormatException();
        }
    }
}
