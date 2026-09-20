<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\PasswordFormatException;

use function mb_strlen;

/**
 * Password — plaintext as submitted. Stored only long enough to hand
 * to PasswordHasherInterface; never persisted in clear form.
 *
 * Nullable: the admin customer-edit flow (doUpdateCustomerProfile)
 * supplies `null` to mean "leave the current hash untouched" (EC-CUBE's
 * default-password sentinel). Flows that REQUIRE a password enforce it
 * at the Input boundary with a non-nullable `string` parameter, so this
 * Semantic only needs to validate the value when one is actually given.
 */
final class Password
{
    #[Validate]
    public function validate(string|null $password): void
    {
        // `null` = "not supplied" (admin-edit sentinel: keep the current
        // hash). An empty string IS a supplied value and stays subject to
        // the length floor, so registration still rejects a blank password.
        if ($password === null) {
            return;
        }

        $length = mb_strlen($password);
        if ($length < 8 || $length > 255) {
            throw new PasswordFormatException();
        }
    }
}
