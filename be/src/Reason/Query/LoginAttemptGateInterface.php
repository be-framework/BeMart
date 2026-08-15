<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Query\Result\LoginFailureCount;
use Ray\MediaQuery\Annotation\DbQuery;

/**
 * Failure counter behind the admin login throttle.
 *
 * Reads the same audit log {@see LoginHistoryStorageInterface} appends to,
 * counting only the failures that came AFTER the newest success for that
 * loginId — a successful login clears the count, so an admin who
 * mistypes twice and then gets in starts from zero again.
 *
 * The policy lives here as constants rather than in the Finals that
 * apply it, so both authentication stages (password and 2FA) and every
 * implementation throttle at the same rate. The numbers are EC-CUBE's:
 * `eccube_login_throttling_max_attempts: 5` /
 * `eccube_login_throttling_interval: '30 minutes'`, which 4.3 hands to
 * Symfony's `login_throttling` on both firewalls. EC-CUBE counts per
 * IP+username in the rate-limiter cache; BeMart counts per loginId in
 * the audit log it already writes, which throttles a distributed
 * guesser too.
 */
interface LoginAttemptGateInterface
{
    /** Consecutive failures tolerated per loginId before an attempt is refused unchecked. */
    public const int MAX_FAILURES = 5;

    /** Sliding window, in minutes, that failures are counted over. */
    public const int WINDOW_MINUTES = 30;

    /**
     * Failures for `$loginId` since its newest success inside the last `$windowMinutes`.
     *
     * Counts by submitted loginId, not by admin record, so attempts
     * against an unregistered loginId are throttled the same way — the
     * response must not tell the two apart.
     */
    #[DbQuery('tlogin_history_recent_failures')]
    public function failuresSinceLastSuccess(string $loginId, int $windowMinutes): LoginFailureCount;
}
