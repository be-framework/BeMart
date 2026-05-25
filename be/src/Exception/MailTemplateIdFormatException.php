<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

#[Message([
    'en' => 'Invalid mail template id.',
    'ja' => 'メールテンプレートIDの形式が不正です。',
])]
final class MailTemplateIdFormatException extends DomainException
{
}
