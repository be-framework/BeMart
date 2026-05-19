<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

#[Message([
    'en' => 'Invalid plugin code format.',
    'ja' => 'プラグインコードの形式が不正です。',
])]
final class PluginCodeFormatException extends DomainException
{
}
