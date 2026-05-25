<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

#[Message([
    'en' => 'The inquiry contents must be 1 to 2000 characters.',
    'ja' => 'お問い合わせ内容は 1 ~ 2000 文字で入力してください。',
])]
final class ContactContentsFormatException extends DomainException
{
}
