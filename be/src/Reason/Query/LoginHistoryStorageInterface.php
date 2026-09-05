<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\LoginHistoryEntity;
use MyVendor\BeMart\Be\Reason\Query\Factory\LoginHistoryFactory;
use Ray\MediaQuery\Annotation\DbQuery;

/**
 * Read/append store for admin login attempt audit rows — the
 * 管理画面ログイン履歴 grid (goLoginHistoryList) and the throttle
 * counter {@see LoginAttemptGateInterface} both read what append()
 * writes.
 *
 * Every authentication decision appends one row, success or failure,
 * so the grid shows attempts that never produced a session and the
 * counter can tell a run of failures from a run interrupted by a
 * success.
 */
interface LoginHistoryStorageInterface
{
    /**
     * Return the login attempt log sorted by timestamp DESC (newest
     * first), capped by `$limit`.
     *
     * @return list<LoginHistoryEntity>
     */
    #[DbQuery('tlogin_history_list', factory: LoginHistoryFactory::class)]
    public function list(int $limit = 50): array;

    /**
     * Append one attempt for the submitted loginId.
     *
     * Takes the loginId as given rather than an admin record: a failed
     * attempt against an unregistered loginId is exactly the row an
     * audit log needs. `create_date` is the database's clock (NOW()),
     * as with every other write in this app, which is also why the
     * append surface is scalars and not a {@see LoginHistoryEntity} —
     * an audit row's timestamp is not the caller's to state.
     */
    #[DbQuery('tlogin_history_insert')]
    public function append(string $loginId, bool $success, string $clientIp): void;
}
