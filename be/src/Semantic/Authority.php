<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\AuthorityFormatException;

/**
 * Admin authority level — ALPS descriptor `authority` (Wave 8).
 *
 * EC-CUBE mtb_authority: 0=システム管理者 (最高権限) / 1=店舗オーナー
 * (制限あり). Lower numeric value = higher privilege. The set is closed
 * — any other value is a malformed request.
 *
 * NOTE: this Semantic validates the SHAPE only ("is the supplied
 * authority a valid value of the set?"). The PRIVILEGE-ESCALATION
 * guard ("can THIS admin promote THAT admin to this authority?") is a
 * runtime AUTHZ check in the relevant Final, not a Semantic check —
 * it depends on session state.
 */
final class Authority
{
    #[Validate]
    public function validate(int $authority): void
    {
        if ($authority !== 0 && $authority !== 1) {
            throw new AuthorityFormatException();
        }
    }
}
