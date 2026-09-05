<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

#[Message([
    'en' => 'Invalid login ID. Must be 1 to 128 characters of A-Z, a-z, 0-9, dot, underscore or hyphen.',
    'ja' => 'ログインIDの形式が不正です。半角英数字と . _ - のみ、1〜128 文字で指定してください。',
])]
final class LoginIdFormatException extends DomainException
{
}
