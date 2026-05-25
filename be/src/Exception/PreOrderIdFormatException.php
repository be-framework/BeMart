<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

#[Message([
    'en' => 'preOrderId must be a 40-character lowercase hexadecimal string.',
    'ja' => 'preOrderId は 40 文字の小文字 16 進数文字列で指定してください。',
])]
final class PreOrderIdFormatException extends DomainException
{
}
