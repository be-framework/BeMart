<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

#[Message([
    'en' => 'Invalid product code format.',
    'ja' => '商品コードの形式が不正です。',
])]
final class ProductCodeFormatException extends DomainException
{
}
