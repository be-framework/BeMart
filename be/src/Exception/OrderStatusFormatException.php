<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

/**
 * Thrown by {@see \MyVendor\BeMart\Be\Semantic\OrderStatus} when the
 * supplied `newStatus` is not one of EC-CUBE's recognised dtb_order_status
 * values (1, 3-9). Distinct from a "transition not permitted" error: this
 * one fires at the input boundary, before any state-machine decision —
 * an unknown status value is malformed, not a denied transition.
 */
#[Message([
    'en' => 'Invalid order status. Expected one of 1, 3, 4, 5, 6, 7, 8, 9.',
    'ja' => '受注ステータスが不正です。1, 3, 4, 5, 6, 7, 8, 9 のいずれかを指定してください。',
])]
final class OrderStatusFormatException extends DomainException
{
}
