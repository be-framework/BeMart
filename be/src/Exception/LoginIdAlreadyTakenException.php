<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

/**
 * Thrown when an admin-create / admin-update operation tries to use a
 * loginId that is already taken by a different admin. Wave 8
 * (doCreateMember / doUpdateMember).
 *
 * Distinct from {@see AdminLoginFailedException}: that one is the
 * authentication-failure case ("wrong loginId or password"); this one
 * is the registration-uniqueness case ("the desired loginId is in use
 * by someone else").
 *
 * Resource layer maps this to HTTP 409 by convention — same shape as
 * customer-side {@see EmailAlreadyRegisteredException}.
 */
#[Message([
    'en' => 'The login ID is already taken.',
    'ja' => 'このログインIDは既に使用されています。',
])]
final class LoginIdAlreadyTakenException extends DomainException
{
}
