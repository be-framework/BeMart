<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

/**
 * Thrown by `doChangePassword` when the new password violates the
 * length policy (EC-CUBE 4.3: 8–32 characters).
 *
 * Resource layer maps this to HTTP 400 by convention.
 */
#[Message([
    'en' => 'The new password must be between 8 and 32 characters.',
    'ja' => 'パスワードは8文字以上32文字以下で入力してください。',
])]
final class PasswordPolicyViolationException extends DomainException
{
}
