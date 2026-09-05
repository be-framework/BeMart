<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\LoginAttemptsExceededException;
use MyVendor\BeMart\Be\Exception\TwoFactorAuthFailedException;
use MyVendor\BeMart\Be\Reason\Query\LoginAttemptGateInterface;
use MyVendor\BeMart\Be\Reason\Query\LoginHistoryStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\ClientIpInterface;
use MyVendor\BeMart\Be\Reason\Service\TwoFactorAuthInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Two-factor auth verified — Final, proof a submitted TOTP code matched
 * the admin's stored secret (doVerifyTwoFactorAuth).
 *
 *   VerifyTwoFactorAuthInput → TwoFactorAuthVerified   (Direct, unsafe)
 *
 * This is a LOGIN-CONTEXT transition (no admin-session AUTHZ guard — the
 * session is elevated by the calling adapter only AFTER this succeeds).
 * A wrong/expired code raises {@see TwoFactorAuthFailedException} (400),
 * with the same generic message whether the secret is missing or the
 * code is wrong (no enumeration).
 *
 * The code is six digits and {@see TwoFactorAuthInterface::verifySecret()}
 * accepts a ±1 step window, so an unbounded retry loop would be worth
 * running: this stage therefore shares the audit log and the throttle of
 * the password stage ({@see AdminAuthenticated}). Every verification —
 * pass or fail — appends a row through {@see LoginHistoryStorageInterface},
 * and {@see LoginAttemptGateInterface} refuses the attempt with
 * {@see LoginAttemptsExceededException} once MAX_FAILURES codes from
 * this client, or MAX_ACCOUNT_FAILURES codes across all clients, have
 * been burned since the last success. The password stage's own success
 * row is what zeroes the count, so a fresh login buys a fresh set of
 * code attempts and nothing more.
 */
final readonly class TwoFactorAuthVerified
{
    public string $loginId;

    public function __construct(
        #[Input] string $loginId,
        #[Input] string $deviceToken,
        #[Inject] TwoFactorAuthInterface $twoFactorAuth,
        #[Inject] LoginHistoryStorageInterface $history,
        #[Inject] LoginAttemptGateInterface $gate,
        #[Inject] ClientIpInterface $clientIp,
    ) {
        $clientAddress = $clientIp->address();
        $gate->failuresSinceLastSuccess($loginId, $clientAddress, LoginAttemptGateInterface::WINDOW_MINUTES)
            ->assertBelow(LoginAttemptGateInterface::MAX_FAILURES);
        $gate->accountFailuresSinceLastSuccess($loginId, LoginAttemptGateInterface::WINDOW_MINUTES)
            ->assertBelow(LoginAttemptGateInterface::MAX_ACCOUNT_FAILURES);

        if (! $twoFactorAuth->verify($loginId, $deviceToken)) {
            $history->append($loginId, false, $clientAddress);

            throw new TwoFactorAuthFailedException();
        }

        $history->append($loginId, true, $clientAddress);

        $this->loginId = $loginId;
    }
}
