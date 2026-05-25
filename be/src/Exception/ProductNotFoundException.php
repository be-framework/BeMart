<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

#[Message([
    'en' => 'Product not found for the given code.',
    'ja' => '指定された商品コードに該当する商品が見つかりません。',
])]
final class ProductNotFoundException extends DomainException
{
}
