<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

/**
 * Thrown when a privilege-escalation guard refuses an admin operation —
 * Wave 8 (doUpdateAuthorityRole, doDeleteMember self-target). The
 * classic role-flip case: a shop-owner (authority=1) tries to lift
 * another admin (or themselves) up to system-admin (authority=0).
 * Lower-numbered authority values mean higher privilege in EC-CUBE;
 * the guard rule is
 *
 *     caller.authority < target.authority  (caller must be strictly higher)
 *
 * Anything else (equal authority included) is refused so a peer cannot
 * silently flip a peer's role. Self-delete also raises this (the ALPS
 * doc says "自身は削除不可" without caveats). Distinct from
 * {@see UnauthorizedAdminAccessException} which is the firewall-level
 * "you are not logged in as an admin at all" case.
 *
 * Resource layer maps this to HTTP 403 by convention — the request was
 * authenticated and the target exists; the calling admin simply lacks
 * the privilege to perform this state change.
 */
#[Message([
    'en' => 'Insufficient authority to perform this operation.',
    'ja' => 'この操作を行う権限がありません。',
])]
final class InsufficientAuthorityException extends DomainException
{
}
