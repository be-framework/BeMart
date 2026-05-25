<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

#[Message([
    'en' => 'Invalid family-name kana. Must contain only katakana characters and be 50 characters or less.',
    'ja' => 'セイの形式が不正です。全角カタカナで 50 文字以下で指定してください。',
])]
final class Kana01FormatException extends DomainException
{
}
