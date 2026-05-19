<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

/**
 * Pilot 8: the request requires an authenticated session but the
 * session reports no customerId. Distinct from
 * UnauthorizedPreOrderAccessException (Pilot 5) which is the AUTHZ
 * case "you're logged in but it's not yours" — this one is the AUTHN
 * case "you need to log in first".
 */
#[Message([
    'en' => 'You must be logged in to perform this action.',
    'ja' => 'この操作を行うにはログインが必要です。',
])]
final class UnauthenticatedException extends DomainException
{
}
