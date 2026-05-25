<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

#[Message([
    'en' => 'Invalid plugin name format.',
    'ja' => 'プラグイン名の形式が不正です。',
])]
final class PluginNameFormatException extends DomainException
{
}
