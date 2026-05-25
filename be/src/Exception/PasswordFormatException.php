<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

#[Message([
    'en' => 'Invalid password. Must be 8 to 255 characters.',
    'ja' => 'パスワードの形式が不正です。8〜255 文字で指定してください。',
])]
final class PasswordFormatException extends DomainException
{
}
