<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

/**
 * Thrown when the current session's customerId does not own the
 * referenced preOrder — i.e. someone is trying to confirm someone
 * else's pre-order. Also thrown when the request is anonymous (no
 * customerId in session) but a customer-scoped preOrder is referenced.
 *
 * Resource layer maps this to HTTP 403.
 */
#[Message([
    'en' => 'You are not authorized to access this pre-order.',
    'ja' => 'この仮注文へのアクセス権限がありません。',
])]
final class UnauthorizedPreOrderAccessException extends DomainException
{
}
