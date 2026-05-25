<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

#[Message([
    'en' => 'Invalid family name. Must be 1 to 50 characters.',
    'ja' => '姓の形式が不正です。1〜50 文字で指定してください。',
])]
final class Name01FormatException extends DomainException
{
}
