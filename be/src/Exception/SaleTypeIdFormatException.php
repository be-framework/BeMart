<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

#[Message([
    'en' => 'Invalid sale type id. Must be a positive integer (1 or more).',
    'ja' => '販売種別 ID が不正です。1 以上の整数で指定してください。',
])]
final class SaleTypeIdFormatException extends DomainException
{
}
