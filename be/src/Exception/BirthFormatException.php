<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

#[Message([
    'en' => 'Invalid birth date. Must be ISO-8601 (YYYY-MM-DD).',
    'ja' => '生年月日の形式が不正です。YYYY-MM-DD 形式で指定してください。',
])]
final class BirthFormatException extends DomainException
{
}
