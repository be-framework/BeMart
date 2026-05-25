<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

/**
 * Thrown when a generic admin list operation (`doSortNoMove` /
 * `doToggleVisible`) names a `masterType` outside the known set
 * ({@see \MyVendor\BeMart\Be\Semantic\MasterType::KNOWN}).
 */
#[Message([
    'en' => 'Unknown admin master type.',
    'ja' => '不明な管理マスタ種別です。',
])]
final class MasterTypeFormatException extends DomainException
{
}
