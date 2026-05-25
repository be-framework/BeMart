<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

#[Message([
    'en' => 'UsePoint must be a non-negative integer.',
    'ja' => '利用ポイントは 0 以上の整数で指定してください。',
])]
final class UsePointFormatException extends DomainException
{
}
