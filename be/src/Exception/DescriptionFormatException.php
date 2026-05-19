<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

#[Message([
    'en' => 'Description is too long.',
    'ja' => '商品説明文が長すぎます。',
])]
final class DescriptionFormatException extends DomainException
{
}
