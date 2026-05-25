<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

/**
 * Thrown when the current session's customerId does not own the
 * referenced AddressEntity — i.e. someone is trying to PUT / DELETE
 * an address that belongs to a different customer. Mirrors
 * {@see UnauthorizedOrderAccessException} (Pilot 12) for the address
 * book domain.
 *
 * Existence is checked before AUTHZ so that a legitimate-but-wrong
 * caller learns 404 vs 403 distinctly — an anonymous request is
 * already rejected upstream by UnauthenticatedException, so this only
 * triggers for a logged-in customer poking at someone else's book.
 *
 * Resource layer maps this to HTTP 403.
 */
#[Message([
    'en' => 'You are not authorized to access this address.',
    'ja' => 'この配送先へのアクセス権限がありません。',
])]
final class UnauthorizedAddressAccessException extends DomainException
{
}
