<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Sql;

use MyVendor\BeMart\Be\Reason\Entity\PageEntity;
use MyVendor\BeMart\Be\Reason\Query\PageStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\PageIdGeneratorInterface;

use function date;

/**
 * Storage-layer coverage for {@see PageStorageInterface} (Phase 2b).
 *
 * Mirrors the shape of {@see NewsStorageInterfaceTest}. Per G-23 the
 * client-observable contract lives in
 * {@see \MyVendor\BeMart\Tests\Resource\Sql\AdminPageResourceSqlTest};
 * the cases below verify the per-method SQL paths in isolation —
 * per-method coverage including miss / empty / boundary / round-trip /
 * dtb_page_layout cascade on remove.
 */
final class SqlPageStorageTest extends AbstractSqlTestCase
{
    public function testListReturnsRowsInIdOrder(): void
    {
        $firstId = $this->insertPage(['page_name' => 'Home']);
        $secondId = $this->insertPage(['page_name' => 'About']);
        $thirdId = $this->insertPage(['page_name' => 'Contact']);

        $storage = $this->sql(PageStorageInterface::class);
        $rows = $storage->list();

        $this->assertCount(3, $rows);
        $this->assertContainsOnlyInstancesOf(PageEntity::class, $rows);
        // ORDER BY id ASC.
        $this->assertSame((string) $firstId, $rows[0]->pageId);
        $this->assertSame((string) $secondId, $rows[1]->pageId);
        $this->assertSame((string) $thirdId, $rows[2]->pageId);
        $this->assertSame('Home', $rows[0]->pageName);
        $this->assertSame('About', $rows[1]->pageName);
        $this->assertSame('Contact', $rows[2]->pageName);
    }

    public function testListReturnsEmptyArrayOnEmptyTable(): void
    {
        $storage = $this->sql(PageStorageInterface::class);
        $this->assertSame([], $storage->list());
    }

    public function testGetByIdReturnsHydratedEntity(): void
    {
        $id = $this->insertPage([
            'page_name' => '会社案内',
            'url' => 'company',
            'file_name' => 'company',
            'edit_type' => 0,
        ]);

        $storage = $this->sql(PageStorageInterface::class);
        $entity = $storage->item((string) $id);

        $this->assertInstanceOf(PageEntity::class, $entity);
        $this->assertSame((string) $id, $entity->pageId);
        $this->assertSame('会社案内', $entity->pageName);
        $this->assertSame('company', $entity->pageUrl);
        $this->assertSame('company', $entity->pageFileName);
        $this->assertSame(0, $entity->pageEditType);
    }

    public function testGetByIdCoercesNullablePageNameAndFileNameToEmptyString(): void
    {
        // page_name / file_name are nullable in EC-CUBE but PageEntity
        // declares them non-null. The hydrator coalesces NULL → '' so
        // the projection shape stays stable across externally-inserted
        // rows.
        $id = $this->insertPage([
            'page_name' => null,
            'file_name' => null,
            'url' => 'bare',
        ]);

        $storage = $this->sql(PageStorageInterface::class);
        $entity = $storage->item((string) $id);

        $this->assertInstanceOf(PageEntity::class, $entity);
        $this->assertSame('', $entity->pageName);
        $this->assertSame('', $entity->pageFileName);
        $this->assertSame('bare', $entity->pageUrl);
    }

    public function testGetByIdReturnsNullForMissingRow(): void
    {
        $storage = $this->sql(PageStorageInterface::class);
        $this->assertNull($storage->item('99999999'));
    }

    public function testGetByIdReturnsNullForNonNumericId(): void
    {
        // The Fake seed `pg-homepage` and hex ids from FakePageIdGenerator
        // can never match an int PK; surface as miss so PageDeleted /
        // PageUpdated / AdminPageFetched fire their 404 paths instead
        // of a PDO error.
        $storage = $this->sql(PageStorageInterface::class);
        $this->assertNull($storage->item('pg-homepage'));
        $this->assertNull($storage->item('pg-deadbeefdeadbeef'));
        $this->assertNull($storage->item('nonexistent-zzz'));
    }

    public function testPutInsertsNewRowWithProvidedId(): void
    {
        $generator = $this->sql(PageIdGeneratorInterface::class);
        $newId = $generator->next()->value; // numeric string

        $entity = new PageEntity(
            pageId: $newId,
            pageName: '会社案内',
            pageUrl: 'company',
            pageFileName: 'company',
            pageEditType: 0,
        );

        $storage = $this->sql(PageStorageInterface::class);
        $storage->put($entity);

        $read = $storage->item($newId);
        $this->assertInstanceOf(PageEntity::class, $read);
        $this->assertSame($newId, $read->pageId);
        $this->assertSame('会社案内', $read->pageName);
        $this->assertSame('company', $read->pageUrl);
        $this->assertSame('company', $read->pageFileName);
        $this->assertSame(0, $read->pageEditType);

        // list() also sees it.
        $all = $storage->list();
        $this->assertCount(1, $all);
        $this->assertSame($newId, $all[0]->pageId);
    }

    public function testPutPersistsEditTypeAsSmallint(): void
    {
        // System pages (edit_type >= 2) round-trip the same as user
        // pages — only PageDeleted enforces the guard, not the storage.
        $generator = $this->sql(PageIdGeneratorInterface::class);
        $newId = $generator->next()->value;
        $storage = $this->sql(PageStorageInterface::class);

        $storage->put(new PageEntity(
            pageId: $newId,
            pageName: 'System',
            pageUrl: 'system',
            pageFileName: 'system',
            pageEditType: 2, // EDIT_TYPE_DEFAULT
        ));

        $read = $storage->item($newId);
        $this->assertInstanceOf(PageEntity::class, $read);
        $this->assertSame(2, $read->pageEditType);

        // Raw column probe — the value is stored as the same smallint.
        $stmt = $this->pdo->prepare(
            'SELECT edit_type FROM dtb_page WHERE id = :id',
        );
        $stmt->execute([':id' => (int) $newId]);
        $row = $stmt->fetch();
        $this->assertNotFalse($row);
        $this->assertSame(2, (int) $row['edit_type']);
    }

    public function testPutIsNoOpForNonNumericIds(): void
    {
        $storage = $this->sql(PageStorageInterface::class);

        $storage->put(new PageEntity(
            pageId: 'pg-homepage',
            pageName: 'Fake-shaped id',
            pageUrl: 'homepage',
            pageFileName: 'index',
            pageEditType: 2,
        ));
        $storage->put(new PageEntity(
            pageId: 'pg-deadbeefdeadbeef',
            pageName: 'hex id',
            pageUrl: 'hex',
            pageFileName: 'hex',
            pageEditType: 0,
        ));

        $this->assertSame([], $storage->list());
    }

    public function testPutUpdatesExistingRowInPlace(): void
    {
        // Insert via fixture (so id is known) then re-put via storage
        // with the same id — exercises the UPDATE branch. ALPS defines
        // doUpdatePage so the UPDATE path is driven by normal admin
        // flows (UpdatePageInput / PageUpdated).
        $id = $this->insertPage([
            'page_name' => 'Old',
            'url' => 'old-url',
            'file_name' => 'old-file',
            'edit_type' => 0,
        ]);

        $merged = new PageEntity(
            pageId: (string) $id,
            pageName: 'New',
            pageUrl: 'new-url',
            pageFileName: 'new-file',
            pageEditType: 0,
        );

        $storage = $this->sql(PageStorageInterface::class);
        $storage->put($merged);

        $read = $storage->item((string) $id);
        $this->assertInstanceOf(PageEntity::class, $read);
        $this->assertSame('New', $read->pageName);
        $this->assertSame('new-url', $read->pageUrl);
        $this->assertSame('new-file', $read->pageFileName);

        // Row count unchanged (no duplicate INSERT).
        $this->assertCount(1, $storage->list());
    }

    public function testRemoveDeletesExistingRow(): void
    {
        $id = $this->insertPage(['page_name' => 'doomed']);
        $storage = $this->sql(PageStorageInterface::class);
        $this->assertNotNull($storage->item((string) $id));

        $storage->delete((string) $id);

        $this->assertNull($storage->item((string) $id));
        $this->assertSame([], $storage->list());
    }

    public function testRemoveCascadesDtbPageLayoutPlacements(): void
    {
        // dtb_page_layout's FK (page_id → dtb_page.id) would otherwise
        // raise FK 1451 on the page DELETE. PageStorageInterface::remove
        // pre-DELETEs the placement rows so the page-level delete
        // succeeds regardless of layout placement state.
        $id = $this->insertPage(['page_name' => 'placed']);

        // Seed a placement row directly. dtb_layout has installer rows
        // (1 = front PC, 2 = front mobile in EC-CUBE); the structure-
        // only dump leaves it empty, so seed a parent layout too.
        // dtb_layout NOT NULL: create_date, update_date,
        // discriminator_type (id auto / layout_name nullable).
        $now = date('Y-m-d H:i:s');
        $this->pdo->prepare(
            'INSERT INTO dtb_layout '
            . '(id, layout_name, create_date, update_date, discriminator_type) '
            . 'VALUES (:id, :name, :created, :updated, :discriminator)',
        )->execute([
            ':id' => 1,
            ':name' => 'PC',
            ':created' => $now,
            ':updated' => $now,
            ':discriminator' => 'layout',
        ]);
        $this->pdo->prepare(
            'INSERT INTO dtb_page_layout '
            . '(page_id, layout_id, sort_no, discriminator_type) '
            . 'VALUES (:page_id, :layout_id, :sort_no, :discriminator)',
        )->execute([
            ':page_id' => $id,
            ':layout_id' => 1,
            ':sort_no' => 0,
            ':discriminator' => 'pagelayout',
        ]);

        $storage = $this->sql(PageStorageInterface::class);
        $storage->delete((string) $id);

        // Page is gone.
        $this->assertNull($storage->item((string) $id));

        // Placement row is also gone (cleanup, not just FK satisfaction).
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM dtb_page_layout WHERE page_id = :id',
        );
        $stmt->execute([':id' => $id]);
        $this->assertSame(0, (int) $stmt->fetchColumn());
    }

    public function testRemoveIsSilentNoOpForMissingId(): void
    {
        $storage = $this->sql(PageStorageInterface::class);
        $storage->delete('99999999'); // no row, no exception
        $storage->delete('pg-homepage'); // non-numeric, no exception
        $storage->delete('pg-deadbeefdeadbeef'); // hex, no exception
        $this->assertTrue(true);
    }

    public function testPageIdGeneratorAllocatesIncrementingIds(): void
    {
        $generator = $this->sql(PageIdGeneratorInterface::class);

        // Empty table → starts at 1.
        $this->assertSame('1', $generator->next()->value);

        $firstId = $this->insertPage();
        $secondId = $this->insertPage();
        $this->assertSame((string) ($secondId + 1), $generator->next()->value);
        $this->assertGreaterThan($firstId, $secondId);
    }
}
