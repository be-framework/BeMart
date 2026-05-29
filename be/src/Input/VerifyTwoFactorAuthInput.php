<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\TwoFactorAuthVerified;

/**
 * Input for `doVerifyTwoFactorAuth` — the admin answers the TOTP
 * challenge after a correct password (Hard ActionRedirect completion).
 *
 *   VerifyTwoFactorAuthInput → TwoFactorAuthVerified   (Direct, unsafe)
 *
 * Login-context transition: it runs AFTER credentials but BEFORE the
 * admin session is fully established, so it carries the candidate
 * `loginId` (the challenge screen round-trips it from the pre-auth step)
 * alongside the `deviceToken`. EC-CUBE's `admin_two_factor_auth`
 * controller verifies the code against the member's stored secret. ALPS
 * marks it `unsafe` — each submission is a fresh verification.
 */
#[Be(TwoFactorAuthVerified::class)]
final readonly class VerifyTwoFactorAuthInput
{
    /**
     * @psalm-taint-source input $loginId
     * @psalm-taint-source input $deviceToken
     */
    public function __construct(
        public string $loginId,
        public string $deviceToken,
    ) {
    }
}
