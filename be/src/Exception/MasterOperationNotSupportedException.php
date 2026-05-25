<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

/**
 * Thrown when a generic admin list operation is requested against a
 * master that does not support it — e.g. `doToggleVisible` on the Tag
 * master (dtb_tag has no `visible` column) or `doSortNoMove` on the
 * News master (dtb_news has no `sort_no` column).
 *
 * Distinct from {@see MasterTypeFormatException} (the masterType is not
 * a known master at all). Here the master IS known, just not capable of
 * this operation. Resource layer maps it to HTTP 400.
 */
#[Message([
    'en' => 'This master does not support the requested operation.',
    'ja' => 'このマスタは指定された操作に対応していません。',
])]
final class MasterOperationNotSupportedException extends DomainException
{
}
