<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\MemberUpdated;

/**
 * Input for doUpdateMember — admin edits another admin's profile
 * (Wave 8).
 *
 * Direct pattern: Input → Final. The Final injects AdminSession for
 * AUTHZ, looks up the target via loginId, and writes the merged
 * record back via AdminCommand.
 *
 * ALPS doc: 管理者情報を更新する。パスワード変更は別画面、本画面は名前・権限・部署のみ。
 *   - The "別画面" (separate-screen) password change is intentionally
 *     out of scope for Wave 8 (Phase 2 will add it).
 *   - "権限" (authority) updates flow through the dedicated
 *     {@see UpdateAuthorityRoleInput} transition so the privilege-
 *     escalation guard stays observable.
 *   - This transition only edits `name` + `mailAddress`.
 *
 * Mass-assignment safety: only the editable fields are accepted here
 * (loginId is the target selector, name / mailAddress are the edit
 * payload). Authority / work / passwordHash CANNOT be reached via
 * this Input — those go through their own dedicated transitions.
 */
#[Be(MemberUpdated::class)]
final readonly class UpdateMemberInput
{
    /**
     * @psalm-taint-source input $loginId
     * @psalm-taint-source input $name
     * @psalm-taint-source input $mailAddress
     */
    public function __construct(
        public string $loginId,
        public string|null $name = null,
        public string|null $mailAddress = null,
    ) {
    }
}
