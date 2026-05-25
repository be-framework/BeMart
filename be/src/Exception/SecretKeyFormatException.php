<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

#[Message([
    'en' => 'Invalid secret key format.',
    'ja' => 'シークレットキーの形式が正しくありません。',
])]
final class SecretKeyFormatException extends DomainException
{
}
