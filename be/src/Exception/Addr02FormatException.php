<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

#[Message([
    'en' => 'Invalid street address. Must be 100 characters or less.',
    'ja' => '番地・建物名の形式が不正です。100 文字以下で指定してください。',
])]
final class Addr02FormatException extends DomainException
{
}
