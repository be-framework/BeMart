<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

#[Message([
    'en' => 'Invalid order items. Expected 1–100 entries of {productCode, productName, unitPrice, quantity}.',
    'ja' => '注文明細が不正です。1〜100件の{productCode, productName, unitPrice, quantity}を指定してください。',
])]
final class OrderItemsFormatException extends DomainException
{
}
