<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

#[Message([
    'en' => 'Invalid quantity. Must be an integer between 1 and 999.',
    'ja' => '数量が不正です。1〜999 の整数で指定してください。',
])]
final class QuantityFormatException extends DomainException
{
}
