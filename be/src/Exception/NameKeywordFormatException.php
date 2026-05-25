<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

#[Message([
    'en' => 'Invalid name keyword. Max length is 100 characters.',
    'ja' => '会員名キーワードが不正です。100文字以内で指定してください。',
])]
final class NameKeywordFormatException extends DomainException
{
}
