<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

#[Message([
    'en' => 'Invalid columns list. Expected 1–100 entries of {columnName, enabled, sortNo}.',
    'ja' => 'columnsリストが不正です。1〜100件の{columnName, enabled, sortNo}を指定してください。',
])]
final class ColumnsFormatException extends DomainException
{
}
