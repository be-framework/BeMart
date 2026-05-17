<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

#[Message([
    'en' => 'Invalid sale limit. Must be null (unlimited) or a positive integer (1 or more).',
    'ja' => '販売制限数が不正です。null（無制限）または 1 以上の整数で指定してください。',
])]
final class SaleLimitFormatException extends DomainException
{
}
