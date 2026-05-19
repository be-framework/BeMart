<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

/**
 * Thrown when the requested deliveryId does not resolve to any row in
 * the admin delivery-method master (Wave 9θ).
 */
#[Message([
    'en' => 'The requested delivery method was not found.',
    'ja' => '指定された配送方法は見つかりませんでした。',
])]
final class DeliveryNotFoundException extends DomainException
{
}
