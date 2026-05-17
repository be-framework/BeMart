<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

#[Message([
    'en' => 'Sale type name must be a non-empty string.',
    'ja' => '販売種別名は空でない文字列で指定してください。',
])]
final class SaleTypeNameFormatException extends DomainException
{
}
