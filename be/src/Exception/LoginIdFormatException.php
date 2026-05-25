<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

#[Message([
    'en' => 'Invalid login ID. Must be 1 to 128 characters.',
    'ja' => 'ログインIDの形式が不正です。1〜128 文字で指定してください。',
])]
final class LoginIdFormatException extends DomainException
{
}
