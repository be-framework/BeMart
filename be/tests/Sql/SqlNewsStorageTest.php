<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Sql;

use MyVendor\BeMart\Be\Reason\Entity\NewsEntity;
use MyVendor\BeMart\Be\Reason\Query\NewsStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\NewsIdQueryInterface;

use function str_contains;
use function strlen;

/**
 * Storage-layer coverage for {@see NewsStorageInterface} (Phase 2b).
 *
 * Mirrors the shape of {@see TaxRuleStorageInterfaceTest}. Per G-23 the
 * client-observable contract lives in
 * {@see \MyVendor\BeMart\Tests\Resource\Sql\AdminNewsResourceSqlTest};
 * the cases below verify the per-method SQL paths in isolation —
 * per-method coverage including miss / empty / boundary / round-trip.
 */
final class SqlNewsStorageTest extends AbstractSqlTestCase
{
    public function testListReturnsRowsInIdOrder(): void
    {
        $firstId = $this->insertNews(['title' => 'Welcome']);
        $secondId = $this->insertNews(['title' => 'Sale']);
        $thirdId = $this->insertNews(['title' => 'Update']);

        $storage = $this->sql(NewsStorageInterface::class);
        $rows = $storage->list();

        $this->assertCount(3, $rows);
        $this->assertContainsOnlyInstancesOf(NewsEntity::class, $rows);
        // ORDER BY id ASC.
        $this->assertSame((string) $firstId, $rows[0]->newsId);
        $this->assertSame((string) $secondId, $rows[1]->newsId);
        $this->assertSame((string) $thirdId, $rows[2]->newsId);
        $this->assertSame('Welcome', $rows[0]->newsTitle);
        $this->assertSame('Sale', $rows[1]->newsTitle);
        $this->assertSame('Update', $rows[2]->newsTitle);
    }

    public function testListReturnsEmptyArrayOnEmptyTable(): void
    {
        $storage = $this->sql(NewsStorageInterface::class);
        $this->assertSame([], $storage->list());
    }

    public function testGetByIdReturnsHydratedEntity(): void
    {
        $id = $this->insertNews([
            'title' => '新店舗オープン',
            'description' => '本文です',
            'url' => 'https://example.com/',
            'publish_date' => '2026-05-01 00:00:00',
            'link_method' => 1,
        ]);

        $storage = $this->sql(NewsStorageInterface::class);
        $entity = $storage->item((string) $id);

        $this->assertInstanceOf(NewsEntity::class, $entity);
        $this->assertSame((string) $id, $entity->newsId);
        $this->assertSame('新店舗オープン', $entity->newsTitle);
        $this->assertSame('本文です', $entity->newsDescription);
        $this->assertSame('https://example.com/', $entity->newsUrl);
        // ISO-8601 with the JST offset baked in (matches the Fake
        // projection's shape).
        $this->assertSame('2026-05-01T00:00:00+09:00', $entity->publishDate);
        $this->assertTrue($entity->linkMethod);
    }

    public function testGetByIdHydratesNullableColumns(): void
    {
        $id = $this->insertNews([
            'title' => 'Bare',
            'description' => null,
            'url' => null,
            'link_method' => 0,
        ]);

        $storage = $this->sql(NewsStorageInterface::class);
        $entity = $storage->item((string) $id);

        $this->assertInstanceOf(NewsEntity::class, $entity);
        $this->assertNull($entity->newsDescription);
        $this->assertNull($entity->newsUrl);
        $this->assertFalse($entity->linkMethod);
    }

    public function testGetByIdReturnsNullForMissingRow(): void
    {
        $storage = $this->sql(NewsStorageInterface::class);
        $this->assertNull($storage->item('99999999'));
    }

    public function testGetByIdReturnsNullForNonNumericId(): void
    {
        // The Fake seed `nw-welcome` and hex ids from FakeNewsIdProvider
        // can never match an int PK; surface as miss so NewsDeleted's
        // 404 path fires instead of a PDO error.
        $storage = $this->sql(NewsStorageInterface::class);
        $this->assertNull($storage->item('nw-welcome'));
        $this->assertNull($storage->item('nw-deadbeefdeadbeef'));
        $this->assertNull($storage->item('nonexistent'));
    }

    public function testPutInsertsNewRowWithProvidedId(): void
    {
        $ids = $this->sql(NewsIdQueryInterface::class);
        $newId = $ids->next()->value; // numeric string

        $entity = new NewsEntity(
            newsId: $newId,
            newsTitle: 'Hello',
            newsDescription: '本文',
            newsUrl: 'https://example.com/',
            publishDate: '2026-05-01T00:00:00+09:00',
            linkMethod: true,
        );

        $storage = $this->sql(NewsStorageInterface::class);
        $storage->put($entity);

        $read = $storage->item($newId);
        $this->assertInstanceOf(NewsEntity::class, $read);
        $this->assertSame($newId, $read->newsId);
        $this->assertSame('Hello', $read->newsTitle);
        $this->assertSame('本文', $read->newsDescription);
        $this->assertSame('https://example.com/', $read->newsUrl);
        $this->assertSame('2026-05-01T00:00:00+09:00', $read->publishDate);
        $this->assertTrue($read->linkMethod);

        // list() also sees it.
        $all = $storage->list();
        $this->assertCount(1, $all);
        $this->assertSame($newId, $all[0]->newsId);
    }

    public function testPutSerialisesIsoDateToMysqlDatetime(): void
    {
        $ids = $this->sql(NewsIdQueryInterface::class);
        $newId = $ids->next()->value;
        $storage = $this->sql(NewsStorageInterface::class);

        $storage->put(new NewsEntity(
            newsId: $newId,
            newsTitle: 'Date Probe',
            newsDescription: null,
            newsUrl: null,
            publishDate: '2026-05-01T00:00:00+09:00',
            linkMethod: false,
        ));

        // Probe the raw column — the storage strips the offset to
        // MySQL `Y-m-d H:i:s` (server-local interpretation per
        // sql/diff/entity-vs-eccube.md "Datetime columns").
        $stmt = $this->pdo->prepare(
            'SELECT publish_date FROM dtb_news WHERE id = :id',
        );
        $stmt->execute([':id' => (int) $newId]);
        $row = $stmt->fetch();
        $this->assertNotFalse($row);
        // Plain MySQL datetime string, no timezone designator.
        $this->assertIsString($row['publish_date']);
        $this->assertSame(19, strlen($row['publish_date']));
        $this->assertFalse(str_contains($row['publish_date'], 'T'));
    }

    public function testPutPersistsLinkMethodAsTinyint(): void
    {
        $ids = $this->sql(NewsIdQueryInterface::class);
        $newId = $ids->next()->value;
        $storage = $this->sql(NewsStorageInterface::class);

        $storage->put(new NewsEntity(
            newsId: $newId,
            newsTitle: 'Link Probe',
            newsDescription: null,
            newsUrl: 'https://example.com/',
            publishDate: '2026-05-01T00:00:00+09:00',
            linkMethod: true,
        ));

        $stmt = $this->pdo->prepare(
            'SELECT link_method FROM dtb_news WHERE id = :id',
        );
        $stmt->execute([':id' => (int) $newId]);
        $row = $stmt->fetch();
        $this->assertNotFalse($row);
        $this->assertSame(1, (int) $row['link_method']);
    }

    public function testPutIsNoOpForNonNumericIds(): void
    {
        $storage = $this->sql(NewsStorageInterface::class);

        $storage->put(new NewsEntity(
            newsId: 'nw-welcome',
            newsTitle: 'Fake-shaped id',
            newsDescription: null,
            newsUrl: null,
            publishDate: '2026-05-01T00:00:00+09:00',
            linkMethod: false,
        ));
        $storage->put(new NewsEntity(
            newsId: 'nw-deadbeefdeadbeef',
            newsTitle: 'hex id',
            newsDescription: null,
            newsUrl: null,
            publishDate: '2026-05-01T00:00:00+09:00',
            linkMethod: false,
        ));

        $this->assertSame([], $storage->list());
    }

    public function testPutUpdatesExistingRowInPlace(): void
    {
        // Insert via fixture (so id is known) then re-put via storage
        // with the same id — exercises the UPDATE branch. Unlike
        // TaxRule, UpdateNewsInput / NewsUpdated drives the UPDATE
        // path during normal admin flows.
        $id = $this->insertNews([
            'title' => 'Old',
            'description' => 'old body',
            'publish_date' => '2026-05-01 00:00:00',
            'link_method' => 0,
        ]);

        $merged = new NewsEntity(
            newsId: (string) $id,
            newsTitle: 'New',
            newsDescription: 'new body',
            newsUrl: 'https://example.com/new',
            publishDate: '2026-06-01T00:00:00+09:00',
            linkMethod: true,
        );

        $storage = $this->sql(NewsStorageInterface::class);
        $storage->put($merged);

        $read = $storage->item((string) $id);
        $this->assertInstanceOf(NewsEntity::class, $read);
        $this->assertSame('New', $read->newsTitle);
        $this->assertSame('new body', $read->newsDescription);
        $this->assertSame('https://example.com/new', $read->newsUrl);
        $this->assertSame('2026-06-01T00:00:00+09:00', $read->publishDate);
        $this->assertTrue($read->linkMethod);

        // Row count unchanged (no duplicate INSERT).
        $this->assertCount(1, $storage->list());
    }

    public function testRemoveDeletesExistingRow(): void
    {
        $id = $this->insertNews(['title' => 'doomed']);
        $storage = $this->sql(NewsStorageInterface::class);
        $this->assertNotNull($storage->item((string) $id));

        $storage->delete((string) $id);

        $this->assertNull($storage->item((string) $id));
        $this->assertSame([], $storage->list());
    }

    public function testRemoveIsSilentNoOpForMissingId(): void
    {
        $storage = $this->sql(NewsStorageInterface::class);
        $storage->delete('99999999'); // no row, no exception
        $storage->delete('nw-welcome'); // non-numeric, no exception
        $storage->delete('nw-deadbeefdeadbeef'); // hex, no exception
        $this->assertTrue(true);
    }

    public function testSetVisibleRewritesVisibleColumn(): void
    {
        $id = $this->insertNews(['visible' => 1]);
        $storage = $this->sql(NewsStorageInterface::class);

        $storage->setVisible((string) $id, false);

        $stmt = $this->pdo->prepare('SELECT visible FROM dtb_news WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        $this->assertNotFalse($row);
        $this->assertSame(0, (int) $row['visible']);

        $storage->setVisible((string) $id, true);
        $stmt->execute([':id' => $id]);
        $back = $stmt->fetch();
        $this->assertNotFalse($back);
        $this->assertSame(1, (int) $back['visible']);
    }

    public function testSetVisibleIsSilentNoOpForNonNumericId(): void
    {
        $storage = $this->sql(NewsStorageInterface::class);
        $storage->setVisible('nw-welcome', false); // non-numeric, no exception
        $this->assertTrue(true);
    }

    public function testNewsIdQueryAllocatesIncrementingIds(): void
    {
        $ids = $this->sql(NewsIdQueryInterface::class);

        // Empty table → starts at 1.
        $this->assertSame('1', $ids->next()->value);

        $firstId = $this->insertNews();
        $secondId = $this->insertNews();
        $this->assertSame((string) ($secondId + 1), $ids->next()->value);
        $this->assertGreaterThan($firstId, $secondId);
    }
}
