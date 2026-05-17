<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

#[Message([
    'en' => 'Tax must be a non-negative integer (yen).',
    'ja' => '消費税は 0 以上の整数（円）で指定してください。',
])]
final class TaxFormatException extends DomainException
{
}
