<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

#[Message([
    'en' => 'Product name cannot be empty.',
    'ja' => '商品名は空にできません。',
])]
final class EmptyProductNameException extends DomainException
{
}
