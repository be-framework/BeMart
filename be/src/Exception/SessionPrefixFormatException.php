<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

#[Message([
    'en' => 'Session prefix must be a non-empty string.',
    'ja' => 'セッションプレフィックスは空でない文字列で指定してください。',
])]
final class SessionPrefixFormatException extends DomainException
{
}
