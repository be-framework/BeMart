<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

#[Message([
    'en' => 'Invalid offset. Expected an integer between 0 and 10000.',
    'ja' => 'オフセットが不正です。0から10000の整数で指定してください。',
])]
final class OffsetFormatException extends DomainException
{
}
