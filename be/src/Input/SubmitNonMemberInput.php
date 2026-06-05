<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\NonMemberSubmitted;

/**
 * Input for doSubmitNonMember — guest-checkout entry (Wave 7W).
 *
 * Direct pattern. The Final validates every field via the existing
 * customer-side Semantics (Email, Name01, Name02, Kana01, Kana02,
 * PhoneNumber, PostalCode, Pref, Addr01, Addr02) and synthesises
 * a preOrderId that the client can hand to a subsequent doCheckout.
 *
 *   SubmitNonMemberInput → NonMemberSubmitted (Final)
 *
 * No AUTHN — anonymous customers can submit guest shipping info.
 *
 * Wave 7W scope is the FORM ENTRY only; integration with Pilot 5's
 * doCheckout is left for Phase 2. See NonMemberSubmitted's docblock
 * for the full Phase 2 gap rationale.
 *
 * @link https://schema.org/CheckoutAction
 */
#[Be(NonMemberSubmitted::class)]
final readonly class SubmitNonMemberInput
{
    /**
     * Phase B Slice 9: every guest field is user-controlled input.
     * Marked as taint sources for the boundary contract; Semantic
     * format-validation runs at metamorphosis time.
     *
     * @psalm-taint-source input $name01
     * @psalm-taint-source input $name02
     * @psalm-taint-source input $kana01
     * @psalm-taint-source input $kana02
     * @psalm-taint-source input $email
     * @psalm-taint-source input $phoneNumber
     * @psalm-taint-source input $postalCode
     * @psalm-taint-source input $pref
     * @psalm-taint-source input $addr01
     * @psalm-taint-source input $addr02
     */
    public function __construct(
        public string $name01,
        public string $name02,
        public string $kana01,
        public string $kana02,
        public string $email,
        public string $phoneNumber,
        public string $postalCode,
        public int $pref,
        public string $addr01,
        public string $addr02,
        public string $sessionPrefix = 'session-prefix-1',
    ) {
    }
}
