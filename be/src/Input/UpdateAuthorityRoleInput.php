<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\AuthorityRoleUpdated;

/**
 * Input for doUpdateAuthorityRole — admin flips another admin's
 * authority level (Wave 8).
 *
 * Direct pattern: Input → Final. Distinct from `doUpdateMember`:
 * authority changes carry privilege-escalation risk, so they go
 * through their own dedicated transition with a stricter guard.
 *
 * ALPS doc: 管理画面の権限ルール（URL 単位の拒否設定）を更新する。
 * Wave 8 implements the role-flip side of this transition (each
 * admin's `authority` column); the "denyUrl per authority" surface
 * (mtb_authority_role) is a Phase 2 sweep.
 *
 * Privilege-escalation rule (Wave 8 critical AUTHZ extension):
 *
 *   caller.authority < target.authority    must hold
 *
 * I.e. the caller MUST have strictly higher privilege than the
 * target's CURRENT authority. Equal-or-lower authority is refused
 * with {@see \MyVendor\BeMart\Be\Exception\InsufficientAuthorityException}
 * (mapped to 403). This is the "no-privilege-escalation" rule:
 *   - Lower-numbered authority = HIGHER privilege in EC-CUBE.
 *   - A shop-owner (authority=1) cannot promote anyone (themselves
 *     or another shop-owner) to system-admin (authority=0).
 *   - Equal-authority peers cannot silently flip each other's role.
 *
 * The new `authority` value itself is bounded by the
 * {@see \MyVendor\BeMart\Be\Semantic\Authority} Semantic (0 or 1).
 */
#[Be(AuthorityRoleUpdated::class)]
final readonly class UpdateAuthorityRoleInput
{
    /**
     * @psalm-taint-source input $loginId
     * @psalm-taint-source input $authority
     */
    public function __construct(
        public string $loginId,
        public int $authority,
    ) {
    }
}
