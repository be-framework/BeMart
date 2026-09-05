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
 * loginId (and, for the per-client counter, that client) — a successful
 * login clears the count, so an admin who mistypes twice and then gets in
 * starts from zero again.
 *
 * The policy lives here as constants rather than in the Finals that
 * apply it, so both authentication stages (password and 2FA) and every
 * implementation throttle at the same rate.
 *
 * Two counters share the audit log. The strict one is per client+loginId:
 * a failure burst from one visitor throttles only that visitor's attempts
 * against that loginId, so one visitor of the public demo cannot lock an
 * account out for everyone else. Its numbers are EC-CUBE's —
 * `eccube_login_throttling_max_attempts: 5` /
 * `eccube_login_throttling_interval: '30 minutes'`, which 4.3 hands to
 * Symfony's `login_throttling` on both firewalls (EC-CUBE counts per
 * IP+username in the rate-limiter cache). The loose counter ignores the
 * client and counts per loginId only; its threshold is 10x the strict
 * one — unreachable for one visitor inside the window, but a bound on
 * distributed guessing across many IPs, which the per-client counter
 * deliberately does not limit. Either counter refusing raises the same
 * {@see \MyVendor\BeMart\Be\Exception\LoginAttemptsExceededException},
 * so the response does not reveal which limit was hit.
 */
interface LoginAttemptGateInterface
{
    /** Failures tolerated per client+loginId before that client's attempts are refused. */
    public const int MAX_FAILURES = 5;

    /**
     * Failures tolerated per loginId across ALL clients before every
     * attempt is refused — 10x MAX_FAILURES, so a single visitor cannot
     * reach it inside the window while a distributed guesser still can.
     */
    public const int MAX_ACCOUNT_FAILURES = 50;

    /** Sliding window, in minutes, that failures are counted over. */
    public const int WINDOW_MINUTES = 30;

    /**
     * Failures for `$loginId` from `$clientIp` since that client's newest
     * success inside the last `$windowMinutes`.
     *
     * The key is the pair, so one client's run of failures never throttles
     * another client against the same loginId, and only a success from the
     * same client clears its count. Counts by submitted loginId, not by
     * admin record, so attempts against an unregistered loginId are
     * throttled the same way — the response must not tell the two apart.
     */
    #[DbQuery('tlogin_history_recent_failures')]
    public function failuresSinceLastSuccess(string $loginId, string $clientIp, int $windowMinutes): LoginFailureCount;

    /**
     * Failures for `$loginId` from ANY client since its newest success
     * inside the last `$windowMinutes`.
     *
     * The loose second counter: ignores the client so a botnet that
     * spreads one attempt per IP is still bounded, while a legitimate
     * visitor stays far below the threshold.
     */
    #[DbQuery('tlogin_history_account_failures')]
    public function accountFailuresSinceLastSuccess(string $loginId, int $windowMinutes): LoginFailureCount;
}
