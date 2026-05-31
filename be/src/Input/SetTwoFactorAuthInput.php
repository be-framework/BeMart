<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\TwoFactorAuthConfigured;

/**
 * Input for `doSetTwoFactorAuth` — the admin registers a TOTP device
 * (Hard ActionRedirect completion).
 *
 *   SetTwoFactorAuthInput → TwoFactorAuthConfigured   (Direct, idempotent)
 *
 * Login-context transition reached when a member has no 2FA device yet.
 * The setup screen shows a QR code for a server-generated secret
 * (`authKey`, round-tripped as a hidden field) and asks the admin to
 * confirm by entering the first `deviceToken`. EC-CUBE's
 * `admin_two_factor_auth_set` controller stores the secret on the member
 * once the first code verifies. ALPS marks it `idempotent` — registering
 * the same secret twice is a no-op.
 */
#[Be(TwoFactorAuthConfigured::class)]
final readonly class SetTwoFactorAuthInput
{
    /**
     * @psalm-taint-source input $loginId
     * @psalm-taint-source input $authKey
     * @psalm-taint-source input $deviceToken
     */
    public function __construct(
        public string $loginId,
        public string $authKey,
        public string $deviceToken,
    ) {
    }
}
