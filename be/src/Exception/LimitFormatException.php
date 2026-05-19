<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

#[Message([
    'en' => 'Invalid limit. Expected an integer between 1 and 50.',
    'ja' => '件数指定が不正です。1から50の整数で指定してください。',
])]
final class LimitFormatException extends DomainException
{
}
