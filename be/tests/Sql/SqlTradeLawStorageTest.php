<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Sql;

use MyVendor\BeMart\Be\Reason\Entity\TradeLawEntity;
use MyVendor\BeMart\Be\Reason\Query\TradeLawStorageInterface;

/**
 * Storage-layer coverage for {@see TradeLawStorageInterface} (Phase 2b).
 *
 * Mirrors the shape of {@see BaseInfoStorageInterfaceTest} — TradeLaw is a
 * singleton-row config in the Wave-8 single-blob interface: the whole
 * 特定商取引法 page body lives in one carrier row at `dtb_tradelaw.id=1`.
 *
 * Per G-23 the client-observable contract lives in
 * {@see \MyVendor\BeMart\Tests\Resource\Sql\AdminTradeLawResourceSqlTest}
 * + {@see \MyVendor\BeMart\Tests\Resource\Sql\AdminTradeLawGetResourceSqlTest};
 * the cases below verify the per-method SQL paths in isolation:
 *
 *   - get() on an empty table → installer-default body Entity
 *     (matching TradeLawStorageInterface's constructor seed, so both
 *     backends produce the IDENTICAL projection on a first read).
 *   - get() on a seeded id=1 row → hydrated Entity carrying that
 *     row's `description`.
 *   - get() when the carrier row exists but `description` is NULL →
 *     installer-default body fall-through (the Entity types `body`
 *     non-null).
 *   - update() on an empty table → INSERT id=1 with the body;
 *     round-trips byte-identical via get().
 *   - update() on an existing id=1 → UPDATE in place; round-trips;
 *     the per-item columns (name / sort_no / display_order_screen)
 *     are left at their seeded values.
 *   - The singleton-row contract — repeated update() never produces
 *     a second row.
 *   - Lossless round-trip of a body containing newlines and colons
 *     (the idempotency contract TradeLawUpdated depends on).
 */
final class SqlTradeLawStorageTest extends AbstractSqlTestCase
{
    private const DEFAULT_BODY = "販売業者: 株式会社EC-CUBE\n"
        . "所在地: 大阪市北区梅田1-1-1\n"
        . '連絡先: 06-1234-5678';

    public function testGetReturnsInstallerDefaultWhenRowMissing(): void
    {
        $storage = $this->sql(TradeLawStorageInterface::class);
        $entity = $storage->item();

        // Mirrors TradeLawStorageInterface's constructor seed — the same
        // contract both backends honour for a first read.
        $this->assertSame(self::DEFAULT_BODY, $entity->body);
    }

    public function testGetHydratesSeededRow(): void
    {
        $this->insertTradeLaw([
            'description' => "販売業者: 既存会社\n所在地: 京都市",
        ]);

        $storage = $this->sql(TradeLawStorageInterface::class);
        $entity = $storage->item();

        $this->assertSame("販売業者: 既存会社\n所在地: 京都市", $entity->body);
    }

    public function testGetFallsBackToDefaultBodyWhenDescriptionIsNull(): void
    {
        // The Entity types `body` as non-null `string`, so the SQL
        // hydrator MUST produce a value even if the carrier row exists
        // with a NULL description. Mirror the installer default — the
        // same string the Fake backend emits.
        $this->insertTradeLaw(['description' => null]);

        $storage = $this->sql(TradeLawStorageInterface::class);
        $entity = $storage->item();

        $this->assertSame(self::DEFAULT_BODY, $entity->body);
    }

    public function testUpdateInsertsCarrierRowWhenTableIsEmpty(): void
    {
        $storage = $this->sql(TradeLawStorageInterface::class);

        $storage->put(new TradeLawEntity(
            body: "販売業者: 新会社\n所在地: 東京都\n連絡先: 03-1234-5678",
        ));

        $read = $storage->item();
        $this->assertSame(
            "販売業者: 新会社\n所在地: 東京都\n連絡先: 03-1234-5678",
            $read->body,
        );
    }

    public function testUpdateInsertsExactlyOneRowWithCarrierId(): void
    {
        $storage = $this->sql(TradeLawStorageInterface::class);
        $storage->put(new TradeLawEntity(body: 'whatever'));

        $stmt = $this->pdo->query('SELECT id FROM dtb_tradelaw');
        $this->assertNotFalse($stmt);
        $ids = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        $this->assertSame([1], array_map('intval', $ids));
    }

    public function testUpdateRewritesExistingRowInPlace(): void
    {
        $this->insertTradeLaw(['description' => '旧本文']);

        $storage = $this->sql(TradeLawStorageInterface::class);
        $storage->put(new TradeLawEntity(body: '新本文'));

        $this->assertSame('新本文', $storage->item()->body);
    }

    public function testUpdatePreservesPerItemColumnsOnRewrite(): void
    {
        // Seed the carrier row with per-item structural values the
        // Wave-8 single-blob interface does not carry. The UPDATE path
        // touches only `description`, so they must survive.
        $this->insertTradeLaw([
            'name' => '販売業者',
            'description' => '旧本文',
            'sort_no' => 7,
            'display_order_screen' => 1,
        ]);

        $storage = $this->sql(TradeLawStorageInterface::class);
        $storage->put(new TradeLawEntity(body: '新本文'));

        $stmt = $this->pdo->query(
            'SELECT name, sort_no, display_order_screen '
            . 'FROM dtb_tradelaw WHERE id = 1',
        );
        $this->assertNotFalse($stmt);
        $row = $stmt->fetch();
        $this->assertNotFalse($row);

        $this->assertSame('販売業者', $row['name']);
        $this->assertSame(7, (int) $row['sort_no']);
        $this->assertSame(1, (int) $row['display_order_screen']);
    }

    public function testUpdateRepeatedDoesNotCreateSecondRow(): void
    {
        $storage = $this->sql(TradeLawStorageInterface::class);

        // First call inserts.
        $storage->put(new TradeLawEntity(body: 'one'));
        // Subsequent calls should UPDATE, never INSERT.
        $storage->put(new TradeLawEntity(body: 'two'));
        $storage->put(new TradeLawEntity(body: 'three'));

        $stmt = $this->pdo->query('SELECT COUNT(*) FROM dtb_tradelaw');
        $this->assertNotFalse($stmt);
        $this->assertSame(1, (int) $stmt->fetchColumn());
        $this->assertSame('three', $storage->item()->body);
    }

    public function testUpdateRoundTripsBodyWithNewlinesAndColonsLosslessly(): void
    {
        // The idempotency contract in TradeLawUpdated compares
        // `get()->body !== $newBody`. A body with embedded newlines,
        // colons and an empty line MUST round-trip byte-identical —
        // this is why TradeLawStorageInterface stores the blob in a single
        // column rather than folding per-item rows.
        $body = "販売業者: A社: B事業部\n\n所在地: 大阪:梅田\n返品: 不可";

        $storage = $this->sql(TradeLawStorageInterface::class);
        $storage->put(new TradeLawEntity(body: $body));

        $this->assertSame($body, $storage->item()->body);

        // A second update with the identical body is a clean no-op
        // round-trip (the storage does the write unconditionally; the
        // Final is what short-circuits).
        $storage->put(new TradeLawEntity(body: $body));
        $this->assertSame($body, $storage->item()->body);
    }
}
