<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

/**
 * Thrown when a submitted two-factor device token does not verify
 * against the admin's stored TOTP secret (doVerifyTwoFactorAuth /
 * doSetTwoFactorAuth).
 *
 * Resource layer maps this to HTTP 400 by convention. Like a password
 * mismatch it is a credential failure, not a malformed-field error; the
 * message stays generic so it does not reveal whether the secret or the
 * code was at fault.
 */
#[Message([
    'en' => 'The authentication code is incorrect.',
    'ja' => '認証コードが正しくありません。',
])]
final class TwoFactorAuthFailedException extends DomainException
{
}
