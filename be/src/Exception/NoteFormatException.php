<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

#[Message([
    'en' => 'Internal note is too long.',
    'ja' => '商品備考が長すぎます。',
])]
final class NoteFormatException extends DomainException
{
}
