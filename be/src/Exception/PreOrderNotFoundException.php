<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

#[Message([
    'en' => 'Pre-order not found for the given preOrderId.',
    'ja' => '指定された preOrderId に対応する仮注文が見つかりません。',
])]
final class PreOrderNotFoundException extends DomainException
{
}
