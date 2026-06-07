<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

#[Message([
    'en' => 'Invalid customer selector. Specify a customer ID or email address.',
    'ja' => '会員セレクタが不正です。会員IDまたはメールアドレスを指定してください。',
])]
final class SelectorFormatException extends DomainException
{
}
