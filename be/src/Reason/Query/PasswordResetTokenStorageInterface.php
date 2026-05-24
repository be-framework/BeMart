<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\PasswordResetTokenEntity;
use Ray\MediaQuery\Annotation\DbQuery;

/**
 * Storage for password-reset tokens — Pilot 14 issues, Pilot 15 consumes.
 *
 * Issuing the same customerId twice REPLACES the prior token
 * (single-use, latest-wins). Pilot 15 (doResetPassword) consumes a
 * token immediately on successful reset by calling `delete()`; a
 * second attempt with the same key then misses on `getByResetKey()`
 * and the consumer raises the merged "wrong / expired / used"
 * exception. ALPS doc: "キーは1回のみ使用可".
 */
interface PasswordResetTokenStorageInterface
{
    #[DbQuery('password_reset_put')]
    public function put(PasswordResetTokenEntity $token): void;

    /** Look up by resetKey (used by doResetPassword consumer). */
    #[DbQuery('password_reset_get')]
    public function byResetKey(string $resetKey): PasswordResetTokenEntity|null;

    /**
     * Consume the token — Pilot 15 (doResetPassword). Removes any
     * entry whose resetKey matches. Silently no-op when the key is
     * unknown so the method is idempotent under retries.
     */
    #[DbQuery('password_reset_delete')]
    public function delete(string $resetKey): void;
}
