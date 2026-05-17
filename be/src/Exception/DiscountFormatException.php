<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

#[Message([
    'en' => 'Discount must be a non-negative integer (yen).',
    'ja' => '値引きは 0 以上の整数（円）で指定してください。',
])]
final class DiscountFormatException extends DomainException
{
}
