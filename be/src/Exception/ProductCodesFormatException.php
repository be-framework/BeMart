<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

#[Message([
    'en' => 'Invalid productCodes list. Expected a non-empty list of valid product codes, max 100 items.',
    'ja' => '商品コードリストが不正です。1〜100件の有効な商品コードを指定してください。',
])]
final class ProductCodesFormatException extends DomainException
{
}
