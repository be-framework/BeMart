<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\MemberFetched;

/**
 * Input for goMember — admin views one admin member's detail (Wave 8).
 *
 *   GetMemberInput → MemberFetched (Final — Direct, safe read)
 *
 * Admin-only endpoint. AUTHZ at the Final via AdminSessionInterface:
 * a null admin session raises UnauthorizedAdminAccessException
 * (BEAR maps to 403). Cross-firewall checks before existence:
 *
 *   1. No admin session     → UnauthorizedAdminAccessException (403)
 *   2. Unknown loginId      → AdminNotFoundException           (404)
 *
 * ALPS doc: 管理者 1 名の詳細を表示する。最終ログイン情報・権限ロールを含む。
 * The "最終ログイン情報" surfacing is Phase 2 — Wave 8 returns the
 * profile + authority only.
 *
 * Key choice: the descriptor for goMember in alps.json is `loginId`,
 * matching EC-CUBE's admin URL pattern (`/admin/setting/system/member/{login_id}`).
 */
#[Be(MemberFetched::class)]
final readonly class GetMemberInput
{
    /**
     * @psalm-taint-source input $loginId
     */
    public function __construct(
        public string $loginId,
    ) {
    }
}
