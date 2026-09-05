<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

/**
 * Too many failed login attempts for one loginId inside the audit window
 * — the attempt is refused before any credential is checked.
 *
 * Raised by both authentication stages ({@see \MyVendor\BeMart\Be\Final\AdminAuthenticated}
 * and {@see \MyVendor\BeMart\Be\Final\TwoFactorAuthVerified}) so a correct
 * password or a guessed TOTP code cannot be confirmed while the lock
 * holds. The message deliberately says nothing about whether the loginId
 * is registered — same anti-enumeration rule as
 * {@see AdminLoginFailedException}, and it must also stay silent about
 * how far the counter has advanced.
 */
#[Message([
    'en' => 'Too many failed login attempts. Try again later.',
    'ja' => 'ログイン試行の回数が上限に達しました。しばらく時間をおいてからやり直してください。',
])]
final class LoginAttemptsExceededException extends DomainException
{
}
