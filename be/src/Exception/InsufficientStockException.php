<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

#[Message([
    'en' => 'Insufficient stock to fulfill the order.',
    'ja' => '在庫が不足しているため注文を確定できません。',
])]
final class InsufficientStockException extends DomainException
{
}
