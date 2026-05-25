<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

/**
 * Login failed — either the email is not registered or the supplied
 * password does not verify against the stored hash.
 *
 * Single exception type by design: discriminating between
 * "no such email" and "wrong password" would leak user-enumeration
 * signal. Both branches raise this same exception.
 */
#[Message([
    'en' => 'Email or password is incorrect.',
    'ja' => 'メールアドレスまたはパスワードが正しくありません。',
])]
final class LoginFailedException extends DomainException
{
}
