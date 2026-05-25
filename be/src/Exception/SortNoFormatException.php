<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

#[Message([
    'en' => 'Sort order must be an integer between 0 and 9999.',
    'ja' => '表示順は0から9999の範囲で入力してください。',
])]
final class SortNoFormatException extends DomainException
{
}
