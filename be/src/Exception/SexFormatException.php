<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

#[Message([
    'en' => 'Invalid sex. Must be 1 (male), 2 (female), 3 (other), or 4 (prefer not to say).',
    'ja' => '性別が不正です。1=男性 / 2=女性 / 3=その他 / 4=回答しない のいずれかで指定してください。',
])]
final class SexFormatException extends DomainException
{
}
