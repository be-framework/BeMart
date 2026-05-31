<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\AdminNotFoundException;
use MyVendor\BeMart\Be\Exception\InvalidCurrentPasswordException;
use MyVendor\BeMart\Be\Exception\PasswordConfirmationMismatchException;
use MyVendor\BeMart\Be\Exception\PasswordPolicyViolationException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Query\AdminCommandInterface;
use MyVendor\BeMart\Be\Reason\Query\AdminQueryInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Service\PasswordHasherInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

use function mb_strlen;

/**
 * Admin password changed — Final, proof the logged-in admin's own
 * credential was re-hashed in place (doChangePassword).
 *
 *   ChangeAdminPasswordInput → AdminPasswordChanged   (Direct, unsafe)
 *
 * AUTHZ + validation ladder (sequencing matters):
 *
 *   1. No admin session              → UnauthorizedAdminAccessException (403)
 *   2. Session adminId not found     → AdminNotFoundException           (404)
 *   3. Current password mismatch     → InvalidCurrentPasswordException  (400)
 *   4. New != confirmation           → PasswordConfirmationMismatchException (400)
 *   5. New length outside 8–32       → PasswordPolicyViolationException  (400)
 *
 * Mass-assignment safety (Pilot 5 F-2 lesson): the target is the session
 * admin (no client-supplied selector), and only `password` is written
 * through the dedicated {@see AdminCommandInterface::updatePasswordHash}
 * narrow surface — loginId / authority / work cannot be reached here.
 */
final readonly class AdminPasswordChanged
{
    /** EC-CUBE 4.3 password policy: 8–32 characters. */
    private const int MIN_LENGTH = 8;
    private const int MAX_LENGTH = 32;

    public string $adminId;
    public string $loginId;

    public function __construct(
        #[Input] string $currentPassword,
        #[Input] string $changePasswordFirst,
        #[Input] string $changePasswordSecond,
        #[Inject] AdminSession $adminSession,
        #[Inject] AdminQueryInterface $adminQuery,
        #[Inject] AdminCommandInterface $adminCommand,
        #[Inject] PasswordHasherInterface $passwordHasher,
    ) {
        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $current = $adminQuery->item($adminSession->adminId);
        if ($current === null) {
            throw new AdminNotFoundException();
        }

        if (! $passwordHasher->verify($currentPassword, $current->passwordHash)) {
            throw new InvalidCurrentPasswordException();
        }

        if ($changePasswordFirst !== $changePasswordSecond) {
            throw new PasswordConfirmationMismatchException();
        }

        $length = mb_strlen($changePasswordFirst);
        if ($length < self::MIN_LENGTH || $length > self::MAX_LENGTH) {
            throw new PasswordPolicyViolationException();
        }

        $adminCommand->updatePasswordHash($current->adminId, $passwordHasher->hash($changePasswordFirst));

        $this->adminId = $current->adminId;
        $this->loginId = $current->loginId;
    }
}
