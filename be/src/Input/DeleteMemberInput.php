<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\MemberDeleted;

/**
 * Input for doDeleteMember — admin soft-deletes another admin (Wave 8).
 *
 * Direct pattern: Input → Final. The Final injects AdminSession for
 * AUTHZ, looks up the target via loginId, flips `work` to NON_ACTIVE.
 *
 * ALPS doc: 管理者アカウントを論理削除する。自身は削除不可。最後のシステム管理者も削除不可。
 *   - "自身は削除不可" (cannot delete self) — enforced in the Final.
 *   - "最後のシステム管理者も削除不可" (cannot delete the last system
 *     admin) — Phase 2 (requires a corpus-wide count which is out of
 *     scope for the first iteration).
 *   - "論理削除" (logical delete) — flips work=0 rather than physically
 *     removing the row, same shape as Wave 6S AdminCustomerDeleted.
 *
 * Idempotency (ALPS `type=idempotent`): a second delete against an
 * already-inactive admin is a no-op — `alreadyDeleted=true`, no
 * second write. Same convention as AdminCustomerDeleted.
 *
 * Mass-assignment safety: only `loginId` (target) is accepted. The
 * caller's adminId comes from the AdminSession.
 */
#[Be(MemberDeleted::class)]
final readonly class DeleteMemberInput
{
    /**
     * @psalm-taint-source input $loginId
     */
    public function __construct(
        public string $loginId,
    ) {
    }
}
