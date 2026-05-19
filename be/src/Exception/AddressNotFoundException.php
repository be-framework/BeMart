<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

/**
 * Thrown when the requested addressId does not resolve to any row in
 * the customer's address book. Pilot 16 (doUpdate / doDelete customer
 * address) raises this when the customer follows a stale URL or
 * fabricates an addressId. DELETE is still 404 on miss — matches
 * Pilot 11 doRemoveCartItem's "no enumeration for legitimate caller"
 * boundary; an anonymous client has already been rejected by
 * UnauthenticatedException, so this code path is reached only by a
 * logged-in customer asking about an id their own book never had.
 *
 * Resource layer maps this to HTTP 404.
 */
#[Message([
    'en' => 'The requested address was not found.',
    'ja' => '指定された配送先は見つかりませんでした。',
])]
final class AddressNotFoundException extends DomainException
{
}
