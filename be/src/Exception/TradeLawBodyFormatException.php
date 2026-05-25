<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

#[Message([
    'en' => 'Invalid trade-law body format.',
    'ja' => '特定商取引法の本文の形式が不正です。',
])]
final class TradeLawBodyFormatException extends DomainException
{
}
