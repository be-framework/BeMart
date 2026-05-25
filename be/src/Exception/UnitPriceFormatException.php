<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

#[Message([
    'en' => 'Unit price is out of allowed range (0 — 9,999,999,999).',
    'ja' => '単価が許容範囲（0 〜 9,999,999,999）外です。',
])]
final class UnitPriceFormatException extends DomainException
{
}
