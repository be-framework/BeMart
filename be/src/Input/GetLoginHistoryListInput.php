<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\LoginHistoryListFetched;

/**
 * Input for goLoginHistoryList — admin views admin-login audit log
 * (Wave 8).
 *
 *   GetLoginHistoryListInput → LoginHistoryListFetched
 *                              (Direct, safe read)
 *
 * Admin-only endpoint. ALPS doc: 管理画面ログイン履歴を表示する。
 * 成功/失敗・IP アドレス・User-Agent を記録. Wave 8 implements the
 * timestamp / loginId / success / clientIp surface; the User-Agent
 * field is Phase 2 (the fake storage does not currently carry it).
 *
 * Minimal Input — only the result cap. No date-range filter for the
 * first iteration; the admin grid renders the recent attempts.
 */
#[Be(LoginHistoryListFetched::class)]
final readonly class GetLoginHistoryListInput
{
    /**
     * @psalm-taint-source input $limit
     */
    public function __construct(
        public int $limit = 50,
    ) {
    }
}
