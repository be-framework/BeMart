<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

/**
 * Thrown when a request reaches an admin-only endpoint without the
 * admin firewall granting access — i.e. the AdminSession
 * reports `$adminId === null`. Typical scenario: a logged-in customer
 * (customer firewall passed) tries to hit `/admin/...` (admin firewall
 * fails, no admin session).
 *
 * Distinct from {@see UnauthenticatedException}: that one is the
 * AUTHN-missing case for the customer firewall ("you need to log in").
 * This one is the AUTHZ-cross-firewall case ("admin login required to
 * access admin endpoints"). The distinction matters because:
 *
 *   - UnauthenticatedException → customer login flow (front-end)
 *   - UnauthorizedAdminAccessException → admin login flow (admin panel)
 *
 * Also distinct from {@see UnauthorizedOrderAccessException}: that one
 * is about ownership within the customer firewall ("this order isn't
 * yours"); this one is about crossing firewalls entirely.
 *
 * Resource layer maps this to HTTP 403 by convention (the request was
 * authenticated to *some* extent — just not as an admin). Wave 5 will
 * exercise this code path; Wave 4 introduces the type so the interface
 * contract is complete before consumers ship.
 */
#[Message([
    'en' => 'Admin login required to access this resource.',
    'ja' => 'この操作には管理者ログインが必要です。',
])]
final class UnauthorizedAdminAccessException extends DomainException
{
}
