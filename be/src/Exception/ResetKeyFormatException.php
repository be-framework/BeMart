<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

#[Message([
    'en' => 'Invalid reset key format.',
    'ja' => 'リセットキーの形式が正しくありません。',
])]
final class ResetKeyFormatException extends DomainException
{
}
