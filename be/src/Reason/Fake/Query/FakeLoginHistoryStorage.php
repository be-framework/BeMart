<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Fake\Query;

use MyVendor\BeMart\Be\Reason\Query\LoginHistoryStorageInterface;
use MyVendor\BeMart\Be\Reason\Entity\LoginHistoryEntity;
use Override;

use function array_slice;
use function strcmp;
use function usort;

/**
 * In-memory login history fake — Wave 8 (goLoginHistoryList).
 *
 * Seeds a handful of sample attempts in the constructor so the admin
 * grid has something to render in tests without needing a JSON
 * fixture. Bound as Singleton in AppModule so appends within a request
 * are visible to subsequent reads.
 */
final class FakeLoginHistoryStorage implements LoginHistoryStorageInterface
{
    /** @var list<LoginHistoryEntity> */
    private array $entries;

    public function __construct()
    {
        $this->entries = [
            new LoginHistoryEntity(
                timestamp: '2026-05-19T09:12:34+09:00',
                loginId: 'test-admin',
                success: true,
                clientIp: '192.0.2.10',
            ),
            new LoginHistoryEntity(
                timestamp: '2026-05-18T22:08:01+09:00',
                loginId: 'test-admin',
                success: false,
                clientIp: '203.0.113.45',
            ),
            new LoginHistoryEntity(
                timestamp: '2026-05-18T18:55:12+09:00',
                loginId: 'shop-owner',
                success: true,
                clientIp: '198.51.100.7',
            ),
            new LoginHistoryEntity(
                timestamp: '2026-05-18T08:00:00+09:00',
                loginId: 'unknown-user',
                success: false,
                clientIp: '203.0.113.99',
            ),
        ];
    }

    /**
     * @return list<LoginHistoryEntity>
     */
    #[Override]
    public function listRecent(int $limit = 50): array
    {
        $rows = $this->entries;
        usort($rows, static fn (LoginHistoryEntity $a, LoginHistoryEntity $b): int
            => strcmp($b->timestamp, $a->timestamp));

        return array_slice($rows, 0, $limit);
    }

    #[Override]
    public function append(LoginHistoryEntity $entry): void
    {
        $this->entries[] = $entry;
    }
}
