<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\TwoFactorAuthFailedException;

use function ctype_digit;
use function strlen;

/**
 * DeviceToken — the 6-digit TOTP code entered on the 2FA challenge /
 * setup screen. A syntactically invalid code can never verify, so it is
 * rejected at the ontology boundary; the cryptographic check happens in
 * the Final via {@see \MyVendor\BeMart\Be\Reason\Service\TwoFactorAuthInterface}.
 */
final class DeviceToken
{
    #[Validate]
    public function validate(string $deviceToken): void
    {
        if (strlen($deviceToken) !== 6 || ! ctype_digit($deviceToken)) {
            throw new TwoFactorAuthFailedException();
        }
    }
}
