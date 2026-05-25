<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

#[Message([
    'en' => 'Invalid product status. Expected 1 (visible), 2 (hidden), or 3 (withdrawn).',
    'ja' => '商品ステータスが不正です。1=公開, 2=非公開, 3=廃止 のいずれかを指定してください。',
])]
final class ProductStatusFormatException extends DomainException
{
}
