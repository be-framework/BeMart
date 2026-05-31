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
 * during the login ladder). The confirmation `deviceToken` is verified
 * against the CANDIDATE `authKey` FIRST
 * ({@see TwoFactorAuthInterface::verifySecret}); only on success is the
 * secret committed via {@see TwoFactorAuthInterface::enable}. A wrong
 * first code raises {@see TwoFactorAuthFailedException} (400) and leaves
 * stored credentials untouched — a bad code can never overwrite an
 * existing 2FA secret. Idempotent: enabling the same secret again is a no-op.
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
        // Verify the first code against the candidate secret BEFORE
        // persisting it, so a wrong code never mutates stored credentials.
        if (! $twoFactorAuth->verifySecret($authKey, $deviceToken)) {
            throw new TwoFactorAuthFailedException();
        }

        // SECURITY CONTRACT: enable() overwrites the secret for $loginId
        // with no ownership check, so the adapter driving this transition
        // MUST bind $loginId to the just-authenticated login (see
        // TwoFactorAuthInterface::enable) — a raw client-supplied $loginId
        // here would allow overwriting another admin's 2FA device.
        $twoFactorAuth->enable($loginId, $authKey);

        $this->loginId = $loginId;
        $this->authKey = $authKey;
    }
}
