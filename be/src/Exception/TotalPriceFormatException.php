<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

#[Message([
    'en' => 'Total price is out of allowed range (0 — 9,999,999,999).',
    'ja' => '合計金額が許容範囲（0 〜 9,999,999,999）外です。',
])]
final class TotalPriceFormatException extends DomainException
{
}
