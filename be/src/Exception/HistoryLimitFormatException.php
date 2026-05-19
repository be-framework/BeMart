<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

#[Message([
    'en' => 'Invalid history limit. Expected an integer between 1 and 200.',
    'ja' => '取得件数が不正です。1から200の整数で指定してください。',
])]
final class HistoryLimitFormatException extends DomainException
{
}
