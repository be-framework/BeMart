<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

#[Message([
    'en' => 'Invalid prefecture id. Must be an integer between 1 (Hokkaido) and 47 (Okinawa).',
    'ja' => '都道府県 ID が不正です。1（北海道）〜47（沖縄県）の整数で指定してください。',
])]
final class PrefFormatException extends DomainException
{
}
