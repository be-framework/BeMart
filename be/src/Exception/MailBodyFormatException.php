<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

#[Message([
    'en' => 'Invalid mail body format.',
    'ja' => 'メール本文の形式が不正です。',
])]
final class MailBodyFormatException extends DomainException
{
}
