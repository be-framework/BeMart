<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

#[Message([
    'en' => 'Invalid job code. Must be an integer between 1 and 18.',
    'ja' => '職業 ID が不正です。1〜18 の整数で指定してください。',
])]
final class JobFormatException extends DomainException
{
}
