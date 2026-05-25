<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

#[Message([
    'en' => 'Invalid postal code. Must be 7 digits or 3-4 digits with hyphen.',
    'ja' => '郵便番号の形式が不正です。7 桁の数字、または「3桁-4桁」で指定してください。',
])]
final class PostalCodeFormatException extends DomainException
{
}
