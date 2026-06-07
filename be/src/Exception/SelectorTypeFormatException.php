<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

#[Message([
    'en' => 'Invalid customer selector type. Use customerId or email.',
    'ja' => '会員セレクタ種別が不正です。customerId または email を指定してください。',
])]
final class SelectorTypeFormatException extends DomainException
{
}
