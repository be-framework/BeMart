<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

#[Message([
    'en' => 'Invalid email. Must contain "@" and be 254 characters or less.',
    'ja' => 'メールアドレスの形式が不正です。"@" を含み 254 文字以下で指定してください。',
])]
final class EmailFormatException extends DomainException
{
}
