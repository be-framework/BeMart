<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

/**
 * Thrown by `doChangePassword` when the two new-password fields
 * (`change_password_first` / `change_password_second`) do not match.
 *
 * Resource layer maps this to HTTP 400 by convention. Mirrors EC-CUBE's
 * RepeatedType confirmation constraint on the password-change form.
 */
#[Message([
    'en' => 'The new password and its confirmation do not match.',
    'ja' => '新しいパスワードと確認用パスワードが一致しません。',
])]
final class PasswordConfirmationMismatchException extends DomainException
{
}
