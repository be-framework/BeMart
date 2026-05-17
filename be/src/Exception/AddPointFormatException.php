<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

#[Message([
    'en' => 'AddPoint must be a non-negative integer.',
    'ja' => '獲得ポイントは 0 以上の整数で指定してください。',
])]
final class AddPointFormatException extends DomainException
{
}
