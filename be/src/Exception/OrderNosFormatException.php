<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

#[Message([
    'en' => 'Invalid orderNos list. Expected a non-empty list of valid order numbers, max 100 items.',
    'ja' => '注文番号リストが不正です。1〜100件の有効な注文番号を指定してください。',
])]
final class OrderNosFormatException extends DomainException
{
}
