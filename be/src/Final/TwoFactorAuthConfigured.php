<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\TwoFactorAuthFailedException;
use MyVendor\BeMart\Be\Reason\Service\TwoFactorAuthInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Two-factor auth configured — Final, proof an admin registered a TOTP
 * device after confirming the first code (doSetTwoFactorAuth).
 *
 *   SetTwoFactorAuthInput → TwoFactorAuthConfigured   (Direct, idempotent)
 *
 * Login-context transition (no admin-session AUTHZ — the device is set up
 * during the login ladder). The secret is registered first, then the
 * confirmation `deviceToken` is verified against it; a wrong first code
 * raises {@see TwoFactorAuthFailedException} (400) — the secret is only
 * "kept" once a code proves the device is in sync. Idempotent: enabling
 * the same secret again is a no-op.
 */
final readonly class TwoFactorAuthConfigured
{
    public string $loginId;
    public string $authKey;

    public function __construct(
        #[Input] string $loginId,
        #[Input] string $authKey,
        #[Input] string $deviceToken,
        #[Inject] TwoFactorAuthInterface $twoFactorAuth,
    ) {
        $twoFactorAuth->enable($loginId, $authKey);
        if (! $twoFactorAuth->verify($loginId, $deviceToken)) {
            throw new TwoFactorAuthFailedException();
        }

        $this->loginId = $loginId;
        $this->authKey = $authKey;
    }
}
