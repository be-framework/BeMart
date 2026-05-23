<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\LoginHistoryEntity;
use Ray\MediaQuery\Annotation\DbQuery;

/**
 * Read/append store for admin login attempt audit rows — Wave 8
 * (goLoginHistoryList).
 *
 * Read-only on the BEAR side for now (the grid lists historical
 * attempts); future Phase 2 will wire append() into the login flow.
 * Wave 8 only needs the grid + a seed fixture, so the interface stays
 * minimal and the storage seeds a few sample rows directly in memory.
 */
interface LoginHistoryStorageInterface
{
    /**
     * Return the login attempt log sorted by timestamp DESC (newest
     * first), capped by `$limit`.
     *
     * @return list<LoginHistoryEntity>
     */
    #[DbQuery('tlogin_history_list', factory: LoginHistoryEntity::class)]
    public function listRecent(int $limit = 50): array;

    /**
     * Append a new attempt — used by future Phase 2 wiring of the
     * admin login flow. Wave 8 introduces the method on the interface
     * for completeness but does NOT yet call it from the Final.
     */
    #[DbQuery('tlogin_history_insert')]
    public function append(LoginHistoryEntity $entry): void;
}
