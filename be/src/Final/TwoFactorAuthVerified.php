<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\TwoFactorAuthFailedException;
use MyVendor\BeMart\Be\Reason\Service\TwoFactorAuthInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Two-factor auth verified — Final, proof a submitted TOTP code matched
 * the admin's stored secret (doVerifyTwoFactorAuth).
 *
 *   VerifyTwoFactorAuthInput → TwoFactorAuthVerified   (Direct, unsafe)
 *
 * This is a LOGIN-CONTEXT transition (no admin-session AUTHZ guard — the
 * session is elevated by the calling adapter only AFTER this succeeds).
 * A wrong/expired code raises {@see TwoFactorAuthFailedException} (400),
 * with the same generic message whether the secret is missing or the
 * code is wrong (no enumeration).
 */
final readonly class TwoFactorAuthVerified
{
    public string $loginId;

    public function __construct(
        #[Input] string $loginId,
        #[Input] string $deviceToken,
        #[Inject] TwoFactorAuthInterface $twoFactorAuth,
    ) {
        if (! $twoFactorAuth->verify($loginId, $deviceToken)) {
            throw new TwoFactorAuthFailedException();
        }

        $this->loginId = $loginId;
    }
}
