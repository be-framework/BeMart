<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

/**
 * Thrown when the requested orderNo does not resolve to any finalized
 * Order in storage. Pilot 12 (doReorder) raises this when the customer
 * follows a stale URL or fabricates an orderNo.
 *
 * Resource layer maps this to HTTP 404.
 */
#[Message([
    'en' => 'The requested order was not found.',
    'ja' => '指定された注文は見つかりませんでした。',
])]
final class OrderNotFoundException extends DomainException
{
}
