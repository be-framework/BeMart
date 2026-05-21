<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

/**
 * Thrown when a generic admin list operation (`doSortNoMove` /
 * `doToggleVisible`) targets a row id that does not resolve to any row
 * in the named master.
 *
 * Resource layer maps this to HTTP 404 by convention. Same ladder as
 * the per-master Update / Delete Finals: cross-firewall AUTHZ
 * ({@see UnauthorizedAdminAccessException}) is checked first, then row
 * existence.
 */
#[Message([
    'en' => 'The requested master row was not found.',
    'ja' => '指定された行は見つかりませんでした。',
])]
final class MasterRowNotFoundException extends DomainException
{
}
