<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

#[Message([
    'en' => 'Invalid city/ward/town. Must be 50 characters or less.',
    'ja' => '市区町村の形式が不正です。50 文字以下で指定してください。',
])]
final class Addr01FormatException extends DomainException
{
}
