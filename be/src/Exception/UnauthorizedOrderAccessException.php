<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

/**
 * Thrown when the current session's customerId does not own the referenced
 * finalized Order — i.e. someone is trying to reorder against someone
 * else's purchase history. Also thrown when the request is anonymous (no
 * customerId in session) but a customer-scoped order is referenced.
 *
 * Distinct from {@see UnauthorizedPreOrderAccessException}: that one
 * guards pre-orders (orderStatus=PROCESSING) during checkout. This one
 * guards finalized orders (orderStatus=NEW onwards) during reorder.
 *
 * Resource layer maps this to HTTP 403.
 */
#[Message([
    'en' => 'You are not authorized to access this order.',
    'ja' => 'この注文へのアクセス権限がありません。',
])]
final class UnauthorizedOrderAccessException extends DomainException
{
}
