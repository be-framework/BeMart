<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

#[Message([
    'en' => 'Order limit must be between 1 and 50.',
    'ja' => '取得件数は1以上50以下で指定してください。',
])]
final class OrderLimitFormatException extends DomainException
{
}
