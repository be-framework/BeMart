<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

#[Message([
    'en' => 'Product name exceeds 255 characters.',
    'ja' => '商品名は255文字以内で入力してください。',
])]
final class ProductNameTooLongException extends DomainException
{
}
