<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\AdminLoginFailedException;
use MyVendor\BeMart\Be\Exception\LoginAttemptsExceededException;
use MyVendor\BeMart\Be\Reason\Entity\AdminEntity;
use MyVendor\BeMart\Be\Reason\Query\AdminQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\LoginAttemptGateInterface;
use MyVendor\BeMart\Be\Reason\Query\LoginHistoryStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\ClientIpInterface;
use MyVendor\BeMart\Be\Reason\Service\PasswordHasherInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;
use SensitiveParameter;

/**
 * Admin authenticated — Final, proof the admin credentials check passed.
 *
 *   AdminLoginInput → AdminAuthenticated  (this stage — verification)
 *
 * Three failure modes all raise AdminLoginFailedException (no
 * enumeration):
 *   1. no admin with that loginId
 *   2. password does not verify
 *   3. the admin is de-provisioned (work != WORK_ACTIVE) — a
 *      soft-deleted member keeps its row and its password hash, so
 *      only the work state stops it from minting a session
 *
 * Each of those three, and the success, appends an audit row through
 * {@see LoginHistoryStorageInterface} — the write is here rather than
 * in the Resource because this constructor is where the verdict is
 * reached, and it is the only place that can record the failure modes
 * without the caller having to re-derive them. Same convergence rule as
 * {@see CheckoutCompleted}: existence of this object proves its side
 * effects ran, and a thrown AdminLoginFailedException proves the failed
 * attempt was logged. Attempts arrive as the submitted loginId, so a
 * login against an unregistered id is recorded too.
 *
 * Before any credential is touched, {@see LoginAttemptGateInterface}
 * refuses an attempt that has already burned through the per-client
 * limit (MAX_FAILURES for that loginId from that client) or the
 * per-account limit (MAX_ACCOUNT_FAILURES for that loginId across all
 * clients) inside WINDOW_MINUTES with
 * {@see LoginAttemptsExceededException}; the refusal is not itself
 * counted or logged, because nothing was attempted, so the window can
 * expire. A correct password does not lift the lock — that is the
 * whole point of the throttle. Either limit raises the same exception,
 * so the response does not tell the caller which one was hit.
 *
 * Existence of this object proves: loginId is registered AND the admin
 * is active AND password matches stored hash. The public surface
 * exposes the adminId and the admin profile fields the BEAR resource
 * needs to populate the session and the response body. The plaintext
 * password is consumed inside the constructor (#[SensitiveParameter])
 * and is intentionally NOT promoted to a public property — mirrors
 * Pilot 6 customer authentication.
 *
 * Distinct from customer-side {@see CustomerAuthenticated}: admins are
 * a different AAA principal class (admin firewall vs customer
 * firewall, per EC-CUBE / Symfony Security convention). The two Final
 * types are not interchangeable even though the shapes are similar.
 */
final readonly class AdminAuthenticated
{
    public string $adminId;
    public string $loginId;
    public string $name;
    public int $authority;

    public function __construct(
        #[Input] string $loginId,
        #[Input] #[SensitiveParameter] string $password,
        #[Inject] AdminQueryInterface $adminQuery,
        #[Inject] PasswordHasherInterface $passwordHasher,
        #[Inject] LoginHistoryStorageInterface $history,
        #[Inject] LoginAttemptGateInterface $gate,
        #[Inject] ClientIpInterface $clientIp,
    ) {
        $clientAddress = $clientIp->address();
        $gate->failuresSinceLastSuccess($loginId, $clientAddress, LoginAttemptGateInterface::WINDOW_MINUTES)
            ->assertBelow(LoginAttemptGateInterface::MAX_FAILURES);
        $gate->accountFailuresSinceLastSuccess($loginId, LoginAttemptGateInterface::WINDOW_MINUTES)
            ->assertBelow(LoginAttemptGateInterface::MAX_ACCOUNT_FAILURES);

        $admin = $adminQuery->byLogin($loginId);
        if ($admin === null) {
            $history->append($loginId, false, $clientAddress);

            throw new AdminLoginFailedException();
        }

        if (! $passwordHasher->verify($password, $admin->passwordHash)) {
            $history->append($loginId, false, $clientAddress);

            throw new AdminLoginFailedException();
        }

        if ($admin->work !== AdminEntity::WORK_ACTIVE) {
            $history->append($loginId, false, $clientAddress);

            throw new AdminLoginFailedException();
        }

        $history->append($admin->loginId, true, $clientAddress);

        $this->adminId = $admin->adminId;
        $this->loginId = $admin->loginId;
        $this->name = $admin->name;
        $this->authority = $admin->authority;
    }
}
