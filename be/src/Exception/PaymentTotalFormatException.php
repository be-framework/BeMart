<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

#[Message([
    'en' => 'PaymentTotal must be a non-negative integer (yen).',
    'ja' => '支払合計は 0 以上の整数（円）で指定してください。',
])]
final class PaymentTotalFormatException extends DomainException
{
}
