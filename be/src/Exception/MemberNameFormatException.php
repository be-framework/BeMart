<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

#[Message([
    'en' => 'Invalid admin display name. Must be 1 to 255 characters.',
    'ja' => '管理者名の形式が不正です。1〜255 文字で指定してください。',
])]
final class MemberNameFormatException extends DomainException
{
}
