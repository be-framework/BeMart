<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\TradeLawEntity;
use Override;
use PDO;

/**
 * Real PDO-backed TradeLaw storage — Phase 2b.
 *
 * Mirrors {@see FakeTradeLawStorage} against the live EC-CUBE 4.3
 * schema (`dtb_tradelaw`). The 特定商取引法 ("Specified Commercial
 * Transactions Act") page is, in EC-CUBE proper, a list of up to 15
 * (name, description) rows the admin grid edits as a unit. The Wave 8
 * BeMart interface — {@see TradeLawStorageInterface} — deliberately
 * forward-declares this NARROW: `get()` returns the page as a single
 * body blob, `update()` replaces it.
 *
 * Single-blob carrier convention (`ROW_ID`):
 *   The interface contract demands a LOSSLESS round-trip — the
 *   idempotency check in {@see \MyVendor\BeMart\Be\Final\TradeLawUpdated}
 *   compares `storage->get()->body !== $newBody`, so a re-submit of the
 *   exact text the previous read produced MUST report changed=false. A
 *   per-row fold (`name: description` joined across rows) cannot
 *   guarantee that round-trip — any newline / colon in a description,
 *   or an empty row, would re-serialize differently. So this Wave-8
 *   impl stores the whole body blob in ONE row's `description` —
 *   `dtb_tradelaw.id = 1` — and reads it straight back. This is the
 *   same singleton-row pattern {@see SqlBaseInfoStorage} uses for
 *   `dtb_base_info.id = 1`: a fixed row identity, no list, no
 *   generator. The remaining structural columns (`name`, `sort_no`,
 *   `display_order_screen`) are EC-CUBE per-item modelling that the
 *   Wave-8 single-blob interface does not yet expose — Phase 2 will
 *   split the body back into per-item rows and start populating them
 *   (see the TradeLawEntity docblock).
 *
 * Schema (`dtb_tradelaw`):
 *   id (int unsigned PK AUTO_INCREMENT), name (varchar(255) NULL),
 *   description (varchar(4000) NULL), sort_no (smallint NOT NULL),
 *   display_order_screen (tinyint(1) NOT NULL),
 *   discriminator_type (varchar(255) NOT NULL). No FK constraints, no
 *   UNIQUE indexes, no timestamps.
 *
 * Coercions:
 *   - `description` is column-nullable but TradeLawEntity::body is a
 *     required non-null `string` → on read, NULL or a missing row
 *     falls back to the installer-default body constant.
 *   - `sort_no` / `display_order_screen` are NOT NULL with no DEFAULT
 *     in the dump → INSERT supplies explicit values (1 / 0). UPDATE
 *     touches only `description` so a row seeded with other values
 *     keeps them.
 *   - `name` is left NULL on INSERT — the Wave-8 blob has no per-item
 *     name; Phase 2 will populate it.
 *
 * The "never null" get contract:
 *   {@see TradeLawStorageInterface::get} must return a TradeLawEntity
 *   even when `dtb_tradelaw` is empty (the structure-only test dump
 *   seeds no rows). This impl mirrors {@see FakeTradeLawStorage}'s
 *   constructor default so both backends produce the IDENTICAL
 *   `installer default` projection on a first read — that is what
 *   keeps the Fake-backed and SQL-backed hypermedia tests asserting
 *   the same shape without per-suite fixture divergence (G-23).
 *
 * Idempotency surface:
 *   TradeLawUpdated compares old vs new and only calls update() when
 *   they differ, so this storage does not itself short-circuit
 *   identical writes — it does the INSERT-or-UPDATE unconditionally.
 *
 * DI is intentionally NOT wired in Phase 2b; FakeTradeLawStorage stays
 * the production-bound implementation. The SQL impl is exercised via
 * the test-only override in AbstractResourceSqlTestCase.
 */
final class SqlTradeLawStorage implements TradeLawStorageInterface
{
    /**
     * Singleton carrier row id — the Wave-8 single-blob body lives in
     * `dtb_tradelaw.id = 1`. EC-CUBE's installer CSV writes id=1 as the
     * first per-item row ("販売業者"); the BeMart Wave-8 slice repurposes
     * that row's `description` column as the whole-page body store.
     */
    private const ROW_ID = 1;

    private const DISCRIMINATOR = 'tradelaw';

    /**
     * Installer-default body — used when `dtb_tradelaw.id = 1` is
     * missing or its `description` is NULL. Matches the body string
     * {@see FakeTradeLawStorage} writes in its constructor, so both
     * backends produce the same default-read projection (G-23).
     */
    private const DEFAULT_BODY = "販売業者: 株式会社EC-CUBE\n"
        . "所在地: 大阪市北区梅田1-1-1\n"
        . '連絡先: 06-1234-5678';

    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    #[Override]
    public function get(): TradeLawEntity
    {
        $stmt = $this->pdo->prepare(
            'SELECT description FROM dtb_tradelaw WHERE id = :id LIMIT 1',
        );
        $stmt->execute([':id' => self::ROW_ID]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false || $row['description'] === null) {
            // Installer-default projection. Mirrors
            // FakeTradeLawStorage::__construct so the Fake-backed and
            // SQL-backed hypermedia tests see the same shape on a
            // first read with no seeded row (G-23).
            return new TradeLawEntity(body: self::DEFAULT_BODY);
        }

        return new TradeLawEntity(body: (string) $row['description']);
    }

    #[Override]
    public function update(TradeLawEntity $entity): void
    {
        $existsStmt = $this->pdo->prepare(
            'SELECT 1 FROM dtb_tradelaw WHERE id = :id LIMIT 1',
        );
        $existsStmt->execute([':id' => self::ROW_ID]);
        $exists = $existsStmt->fetchColumn() !== false;

        if ($exists) {
            // Touch only `description` — a row seeded with a per-item
            // name / sort_no / display_order_screen keeps those values.
            $stmt = $this->pdo->prepare(
                'UPDATE dtb_tradelaw SET description = :description '
                . 'WHERE id = :id',
            );
            $stmt->execute([
                ':id' => self::ROW_ID,
                ':description' => $entity->body,
            ]);

            return;
        }

        // First write — INSERT the carrier row with an explicit id=1.
        // `name` is NULL (the Wave-8 blob has no per-item name);
        // sort_no / display_order_screen are NOT NULL with no DEFAULT
        // so we supply 1 / 0. discriminator_type is 'tradelaw' (the
        // Doctrine single-table inheritance value EC-CUBE writes).
        $stmt = $this->pdo->prepare(
            'INSERT INTO dtb_tradelaw '
            . '(id, name, description, sort_no, display_order_screen, '
            . 'discriminator_type) '
            . 'VALUES (:id, :name, :description, :sort_no, '
            . ':display_order_screen, :discriminator)',
        );
        $stmt->execute([
            ':id' => self::ROW_ID,
            ':name' => null,
            ':description' => $entity->body,
            ':sort_no' => 1,
            ':display_order_screen' => 0,
            ':discriminator' => self::DISCRIMINATOR,
        ]);
    }
}
