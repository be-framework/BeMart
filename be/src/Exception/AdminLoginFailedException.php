<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

/**
 * Admin login failed — either the loginId is not registered or the
 * supplied password does not verify against the stored hash.
 *
 * Single exception type by design: discriminating between
 * "no such loginId" and "wrong password" would leak admin-enumeration
 * signal. Both branches raise this same exception — same
 * anti-enumeration design as customer-side
 * {@see LoginFailedException}, but a separate type so admin auth
 * failures can be filtered for audit logs distinctly.
 */
#[Message([
    'en' => 'Login ID or password is incorrect.',
    'ja' => 'ログインIDまたはパスワードが正しくありません。',
])]
final class AdminLoginFailedException extends DomainException
{
}
