<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

#[Message([
    'en' => 'Invalid news URL format.',
    'ja' => 'ニュースの外部URLの形式が不正です。',
])]
final class NewsUrlFormatException extends DomainException
{
}
