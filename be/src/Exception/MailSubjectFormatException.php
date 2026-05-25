<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

#[Message([
    'en' => 'Invalid mail subject format.',
    'ja' => 'メール件名の形式が不正です。',
])]
final class MailSubjectFormatException extends DomainException
{
}
