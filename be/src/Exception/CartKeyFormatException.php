<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

#[Message([
    'en' => 'Invalid cart key. Expected format: {sessionPrefix}_{saleTypeId}.',
    'ja' => 'カートキーが不正です。{sessionPrefix}_{saleTypeId} 形式で指定してください。',
])]
final class CartKeyFormatException extends DomainException
{
}
