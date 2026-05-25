<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

/**
 * Thrown when an admin-side lookup queries an admin (by adminId /
 * loginId) and no such admin exists in storage. Wave 8 (goMember /
 * doUpdateMember / doDeleteMember / doUpdateAuthorityRole).
 *
 * Resource layer maps this to HTTP 404 by convention. Sequencing
 * matters: cross-firewall AUTHZ
 * ({@see UnauthorizedAdminAccessException}) is checked first, then
 * existence — same ladder as Wave 5 / 6 customer-side admin
 * endpoints.
 */
#[Message([
    'en' => 'The requested admin member was not found.',
    'ja' => '指定された管理者は見つかりませんでした。',
])]
final class AdminNotFoundException extends DomainException
{
}
