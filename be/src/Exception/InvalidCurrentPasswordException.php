<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

/**
 * Thrown by `doChangePassword` when the submitted current password does
 * not verify against the logged-in admin's stored hash.
 *
 * Resource layer maps this to HTTP 400 by convention. AUTHZ
 * ({@see UnauthorizedAdminAccessException}) is checked first; this is a
 * post-authentication credential re-check, mirroring EC-CUBE which
 * requires the current password before applying a new one.
 */
#[Message([
    'en' => 'The current password is incorrect.',
    'ja' => '現在のパスワードが正しくありません。',
])]
final class InvalidCurrentPasswordException extends DomainException
{
}
