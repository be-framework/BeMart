<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

#[Message([
    'en' => 'Invalid phone number. Must be 10 to 13 digits (hyphens allowed).',
    'ja' => '電話番号の形式が不正です。10〜13 桁の数字（ハイフン可）で指定してください。',
])]
final class PhoneNumberFormatException extends DomainException
{
}
