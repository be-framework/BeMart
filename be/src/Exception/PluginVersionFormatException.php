<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

#[Message([
    'en' => 'Invalid plugin version format.',
    'ja' => 'プラグインのバージョン形式が不正です。',
])]
final class PluginVersionFormatException extends DomainException
{
}
