<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

#[Message([
    'en' => 'Invalid email keyword. Max length is 255 characters.',
    'ja' => 'メールキーワードが不正です。255文字以内で指定してください。',
])]
final class EmailKeywordFormatException extends DomainException
{
}
