<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Sql;

use MyVendor\BeMart\Be\Reason\Entity\LoginHistoryEntity;
use MyVendor\BeMart\Be\Reason\Query\SqlLoginHistoryStorage;

use function str_contains;
use function strlen;

/**
 * Storage-layer coverage for {@see SqlLoginHistoryStorage} (Phase 2b).
 *
 * Mirrors the shape of {@see SqlNewsStorageTest}. Per G-23 the
 * client-observable contract lives in
 * {@see \MyVendor\BeMart\Tests\Resource\Sql\AdminLoginHistoryResourceSqlTest};
 * the cases below verify the per-method SQL paths in isolation —
 * per-method coverage including empty / boundary / round-trip /
 * status-enum projection.
 *
 * Every test seeds mtb_login_history_status first: the FK on
 * dtb_login_history.login_history_status_id is NOT NULL and the master
 * table is empty in the structure-only dump.
 */
final class SqlLoginHistoryStorageTest extends AbstractSqlTestCase
{
    public function testListReturnsEmptyArrayOnEmptyTable(): void
    {
        $storage = new SqlLoginHistoryStorage($this->pdo);
        $this->assertSame([], $storage->listRecent());
    }

    public function testListReturnsNewestFirst(): void
    {
        $this->seedLoginHistoryStatus();
        $this->insertLoginHistory([
            'user_name' => 'oldest',
            'create_date' => '2026-05-18 08:00:00',
        ]);
        $this->insertLoginHistory([
            'user_name' => 'newest',
            'create_date' => '2026-05-19 09:12:34',
        ]);
        $this->insertLoginHistory([
            'user_name' => 'middle',
            'create_date' => '2026-05-18 22:08:01',
        ]);

        $storage = new SqlLoginHistoryStorage($this->pdo);
        $rows = $storage->listRecent();

        $this->assertCount(3, $rows);
        $this->assertContainsOnlyInstancesOf(LoginHistoryEntity::class, $rows);
        // ORDER BY create_date DESC — newest first.
        $this->assertSame('newest', $rows[0]->loginId);
        $this->assertSame('middle', $rows[1]->loginId);
        $this->assertSame('oldest', $rows[2]->loginId);
    }

    public function testListRespectsLimit(): void
    {
        $this->seedLoginHistoryStatus();
        for ($i = 0; $i < 5; $i++) {
            $this->insertLoginHistory();
        }

        $storage = new SqlLoginHistoryStorage($this->pdo);
        $this->assertCount(2, $storage->listRecent(2));
        $this->assertCount(5, $storage->listRecent(50));
    }

    public function testListWithNonPositiveLimitReturnsNothing(): void
    {
        // The Fake's array_slice treats a non-positive length as
        // "nothing"; SqlLoginHistoryStorage clamps to LIMIT 0 rather
        // than emitting a negative LIMIT (a parse error).
        $this->seedLoginHistoryStatus();
        $this->insertLoginHistory();

        $storage = new SqlLoginHistoryStorage($this->pdo);
        $this->assertSame([], $storage->listRecent(0));
        $this->assertSame([], $storage->listRecent(-1));
    }

    public function testListProjectsStatusIdToSuccessBool(): void
    {
        $this->seedLoginHistoryStatus();
        $this->insertLoginHistory([
            'user_name' => 'won',
            'login_history_status_id' => 1,
            'create_date' => '2026-05-19 10:00:00',
        ]);
        $this->insertLoginHistory([
            'user_name' => 'lost',
            'login_history_status_id' => 0,
            'create_date' => '2026-05-19 09:00:00',
        ]);

        $storage = new SqlLoginHistoryStorage($this->pdo);
        $rows = $storage->listRecent();

        $this->assertSame('won', $rows[0]->loginId);
        $this->assertTrue($rows[0]->success);
        $this->assertSame('lost', $rows[1]->loginId);
        $this->assertFalse($rows[1]->success);
    }

    public function testListEmitsIsoTimestampWithJstOffset(): void
    {
        $this->seedLoginHistoryStatus();
        $this->insertLoginHistory([
            'user_name' => 'probe',
            'create_date' => '2026-05-19 09:12:34',
        ]);

        $storage = new SqlLoginHistoryStorage($this->pdo);
        $rows = $storage->listRecent();

        // MySQL `Y-m-d H:i:s` re-emitted as ISO-8601 with the JST
        // offset (matches the Fake projection's shape).
        $this->assertSame('2026-05-19T09:12:34+09:00', $rows[0]->timestamp);
    }

    public function testAppendInsertsRow(): void
    {
        $this->seedLoginHistoryStatus();
        $storage = new SqlLoginHistoryStorage($this->pdo);

        $storage->append(new LoginHistoryEntity(
            timestamp: '2026-05-20T14:30:00+09:00',
            loginId: 'test-admin',
            success: true,
            clientIp: '198.51.100.7',
        ));

        $rows = $storage->listRecent();
        $this->assertCount(1, $rows);
        $this->assertSame('test-admin', $rows[0]->loginId);
        $this->assertTrue($rows[0]->success);
        $this->assertSame('198.51.100.7', $rows[0]->clientIp);
        $this->assertSame('2026-05-20T14:30:00+09:00', $rows[0]->timestamp);
    }

    public function testAppendPersistsFailureAsStatusZero(): void
    {
        $this->seedLoginHistoryStatus();
        $storage = new SqlLoginHistoryStorage($this->pdo);

        $storage->append(new LoginHistoryEntity(
            timestamp: '2026-05-20T14:30:00+09:00',
            loginId: 'bad-actor',
            success: false,
            clientIp: '203.0.113.99',
        ));

        // Probe the raw column — success=false → login_history_status_id 0.
        $stmt = $this->pdo->prepare(
            'SELECT login_history_status_id, member_id, discriminator_type '
            . 'FROM dtb_login_history WHERE user_name = :user_name',
        );
        $stmt->execute([':user_name' => 'bad-actor']);
        $row = $stmt->fetch();
        $this->assertNotFalse($row);
        $this->assertSame(0, (int) $row['login_history_status_id']);
        // member_id is always NULL (dtb_member is empty in the dump).
        $this->assertNull($row['member_id']);
        $this->assertSame('login_history', $row['discriminator_type']);

        $rows = $storage->listRecent();
        $this->assertFalse($rows[0]->success);
    }

    public function testAppendSerialisesIsoDateToMysqlDatetime(): void
    {
        $this->seedLoginHistoryStatus();
        $storage = new SqlLoginHistoryStorage($this->pdo);

        $storage->append(new LoginHistoryEntity(
            timestamp: '2026-05-20T14:30:00+09:00',
            loginId: 'date-probe',
            success: true,
            clientIp: '192.0.2.1',
        ));

        // Probe the raw column — the storage strips the offset to MySQL
        // `Y-m-d H:i:s` (server-local interpretation per
        // sql/diff/entity-vs-eccube.md "Datetime columns").
        $stmt = $this->pdo->prepare(
            'SELECT create_date, update_date FROM dtb_login_history '
            . 'WHERE user_name = :user_name',
        );
        $stmt->execute([':user_name' => 'date-probe']);
        $row = $stmt->fetch();
        $this->assertNotFalse($row);
        $this->assertIsString($row['create_date']);
        $this->assertSame(19, strlen($row['create_date']));
        $this->assertFalse(str_contains($row['create_date'], 'T'));
        // update_date mirrors create_date (an audit row is never mutated).
        $this->assertSame($row['create_date'], $row['update_date']);
    }

    public function testAppendThenListRoundTripsNewestFirst(): void
    {
        $this->seedLoginHistoryStatus();
        $storage = new SqlLoginHistoryStorage($this->pdo);

        $storage->append(new LoginHistoryEntity(
            timestamp: '2026-05-18T08:00:00+09:00',
            loginId: 'first',
            success: false,
            clientIp: '203.0.113.1',
        ));
        $storage->append(new LoginHistoryEntity(
            timestamp: '2026-05-20T08:00:00+09:00',
            loginId: 'second',
            success: true,
            clientIp: '203.0.113.2',
        ));

        $rows = $storage->listRecent();
        $this->assertCount(2, $rows);
        // Newest append timestamp first regardless of insert order.
        $this->assertSame('second', $rows[0]->loginId);
        $this->assertSame('first', $rows[1]->loginId);
    }
}
