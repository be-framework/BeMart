<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\PasswordResetTokenEntity;

/**
 * Storage for password-reset tokens — Pilot 14.
 *
 * Issuing the same customerId twice REPLACES the prior token
 * (single-use, latest-wins). doResetPassword (deferred to a later
 * Pilot) will consume + delete; this interface only handles the
 * issue side.
 */
interface PasswordResetTokenStorageInterface
{
    public function put(PasswordResetTokenEntity $token): void;

    /** Look up by resetKey (for the future doResetPassword pilot). */
    public function getByResetKey(string $resetKey): PasswordResetTokenEntity|null;
}
