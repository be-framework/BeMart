<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\LoginHistoryEntity;
use Override;

use function date;
use function strtotime;

final class SqlLoginHistoryStorage implements LoginHistoryStorageInterface
{
    public function __construct(private readonly MediaQueryExecutor $db) {}

    /** @return list<LoginHistoryEntity> */
    #[Override]
    public function listRecent(int $limit = 50): array
    {
        return array_map($this->hydrate(...), $this->db->rows('tlogin_history_list', ['limit' => $limit]));
    }

    #[Override]
    public function append(LoginHistoryEntity $entry): void
    {
        $this->db->exec('tlogin_history_insert', [
            'statusId' => $entry->success ? 1 : 2,
            'loginId' => $entry->loginId,
            'clientIp' => $entry->clientIp,
            'created' => $this->toMysqlDatetime($entry->timestamp),
        ]);
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): LoginHistoryEntity
    {
        return new LoginHistoryEntity(
            timestamp: (string) ($row['create_date'] ?? ''),
            loginId: (string) ($row['login_id'] ?? ''),
            success: (int) ($row['status_id'] ?? 0) === 1,
            clientIp: (string) ($row['client_ip'] ?? ''),
        );
    }

    private function toMysqlDatetime(string $value): string
    {
        $time = strtotime($value);
        return $time === false ? $value : date('Y-m-d H:i:s', $time);
    }
}
