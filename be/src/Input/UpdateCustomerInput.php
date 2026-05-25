<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\CustomerUpdated;

/**
 * Input for doUpdateCustomer — the logged-in customer is editing
 * their own profile (Pilot 8).
 *
 * Direct pattern: Input → Final. The Final injects SessionInterface
 * to resolve the customerId, looks up the current state via
 * CustomerQuery, and writes the merged record back via
 * CustomerCommand.
 *
 * AUTHZ design — mass-assignment safety:
 *   The customerId is INTENTIONALLY ABSENT from this Input. It comes
 *   exclusively from the session. A logged-in user editing the body
 *   cannot smuggle a different customerId into the update; the
 *   request body simply doesn't accept one. (Pilot 5 F-2 lesson
 *   carried forward.)
 *
 * The email is required (its ALPS descriptor lists `email` as the
 * single required field) — passing the SAME email as the current
 * record is fine (uniqueness check is bypassed for the no-change
 * case). Every other field is nullable — a `null` value means "do
 * not change this field on the persisted record".
 *
 * Password change is intentionally out of scope for Pilot 8 — see
 * doRequestPasswordReset / doResetPassword (Pilot 14) for the dedicated
 * flow.
 *
 * @link https://schema.org/UpdateAction
 */
#[Be(CustomerUpdated::class)]
final readonly class UpdateCustomerInput
{
    /**
     * @psalm-taint-source input $email
     * @psalm-taint-source input $name01
     * @psalm-taint-source input $name02
     * @psalm-taint-source input $kana01
     * @psalm-taint-source input $kana02
     * @psalm-taint-source input $companyName
     * @psalm-taint-source input $phoneNumber
     * @psalm-taint-source input $postalCode
     * @psalm-taint-source input $pref
     * @psalm-taint-source input $addr01
     * @psalm-taint-source input $addr02
     */
    public function __construct(
        public string $email,
        public string|null $name01 = null,
        public string|null $name02 = null,
        public string|null $kana01 = null,
        public string|null $kana02 = null,
        public string|null $companyName = null,
        public string|null $phoneNumber = null,
        public string|null $postalCode = null,
        public int|null $pref = null,
        public string|null $addr01 = null,
        public string|null $addr02 = null,
    ) {
    }
}
