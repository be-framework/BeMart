<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\BlockEntity;
use Override;
use PDO;

use function ctype_digit;

/**
 * Real PDO-backed Block storage — Phase 2b.
 *
 * Mirrors {@see FakeBlockStorage} against the live EC-CUBE 4.3 schema
 * (`dtb_block`). The table is grade A — the 4-field BlockEntity
 * projection (blockId / blockName / blockFileName / blockDeletable)
 * lines up 1:1 with EC-CUBE columns (id / block_name / file_name /
 * deletable), with two coercions at the boundary.
 *
 * Scope (Wave 9 — same as BlockEntity):
 *   The 4-field projection above. EC-CUBE has four more columns on
 *   dtb_block (device_type_id, use_controller, create_date,
 *   update_date) and a sibling join table `dtb_block_position`. None
 *   are part of BlockStorageInterface — the admin flat-list / CRUD UX
 *   only edits the 4 projected fields, and layout composition is Phase
 *   2 scope. device_type_id is always written NULL (EC-CUBE's
 *   per-device variant mechanism — out of scope for the BeMart admin
 *   slice and mtb_device_type is empty in the structure-only dump so a
 *   non-NULL value would raise FK 1452); use_controller is always
 *   written 0 (no UI for it, matches EC-CUBE's plain-template default);
 *   create_date / update_date are maintained with NOW() (same Doctrine
 *   Timestampable behavior {@see SqlPageStorage} mimics). No
 *   dtb_block_position row is written on INSERT — the join table is
 *   composite-PK (section, block_id, layout_id) so absence simply
 *   means "block is not placed on any layout", which is semantically
 *   valid: EC-CUBE's admin allows block existence without placement.
 *   On DELETE we issue a defensive
 *   `DELETE FROM dtb_block_position WHERE block_id = ?` first to keep
 *   the FK (FK_35DCD731E9ED820C) from blocking the block deletion if a
 *   row was seeded externally — same shape SqlPageStorage uses against
 *   dtb_page_layout.
 *
 * Coercions:
 *   - `id` is `int unsigned`, BlockEntity::blockId is `string`
 *     → cast `(string) (int)` on read, parse with `ctype_digit` on
 *     write. A non-numeric incoming blockId (e.g. `bk-header` from the
 *     Fake seed or hex from {@see FakeBlockIdGenerator}) is rejected:
 *     getById returns null, put no-ops, remove no-ops. Keeps
 *     {@see BlockDeleted} / {@see BlockUpdated} on their normal 404
 *     path instead of raising a PDO exception — same shape the Fake
 *     exhibits when the id is absent. Same convention as
 *     {@see SqlPageStorage} / {@see SqlNewsStorage} /
 *     {@see SqlTagStorage} / {@see SqlTaxRuleStorage}.
 *   - `block_name` is nullable in EC-CUBE but non-null on BlockEntity.
 *     Hydrator coerces NULL → '' so the projection's non-null shape is
 *     preserved across externally-inserted rows.
 *   - `deletable` is `tinyint(1) NOT NULL DEFAULT 1`,
 *     BlockEntity::blockDeletable is `bool` → cast `(bool) (int)` on
 *     read, `(int)` on write.
 *
 * Upsert convention (`put`):
 *   blockId is pre-allocated by {@see SqlBlockIdGenerator} before
 *   `put` is called (BlockCreated assigns `$entity->blockId` from the
 *   generator output, so the storage receives an id-bearing entity).
 *   `put` probes `SELECT 1 WHERE id = ?`; hit → UPDATE, miss → INSERT
 *   with the explicit id. discriminator_type is 'block' (the value
 *   EC-CUBE writes — Doctrine single-table inheritance discriminator
 *   defaults to the lowercased class name on Eccube\Entity\Block).
 *
 *   ALPS defines `doUpdateBlock` (BlockUpdated Final flows merges +
 *   put on the same id), so the UPDATE branch is actively exercised,
 *   same as the News / Page flows.
 *
 * Timestamps: NOW() on insert for both `create_date` and `update_date`;
 * NOW() on `update_date` only for updates (matches the Doctrine
 * Timestampable behavior EC-CUBE relies on). No timezone column on
 * dtb_block — datetime is interpreted server-local same as
 * SqlPageStorage / SqlNewsStorage.
 *
 * List ordering: `ORDER BY id ASC` — the contract test does not
 * assert order, only count and presence. Same shape parity convention
 * as SqlPageStorage / SqlNewsStorage / SqlTagStorage /
 * SqlTaxRuleStorage / SqlAddressStorage.
 *
 * DI is intentionally NOT wired in production (FakeBlockStorage
 * remains the bound implementation). The SQL impl is exercised via
 * the test-only override in AbstractResourceSqlTestCase.
 */
final class SqlBlockStorage implements BlockStorageInterface
{
    private const SELECT_COLUMNS = 'id, block_name, file_name, deletable';

    private const DISCRIMINATOR = 'block';

    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    /** @return list<BlockEntity> */
    #[Override]
    public function list(): array
    {
        $sql = 'SELECT ' . self::SELECT_COLUMNS . ' FROM dtb_block '
            . 'ORDER BY id ASC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        $out = [];
        while (($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
            $out[] = $this->hydrate($row);
        }

        return $out;
    }

    #[Override]
    public function getById(string $blockId): BlockEntity|null
    {
        if (! ctype_digit($blockId)) {
            // Non-numeric ids (e.g. `bk-header` Fake seed, hex from
            // FakeBlockIdGenerator) can never match an int PK. Surface
            // as miss so BlockDeleted / BlockUpdated raise their normal
            // 404 instead of throwing a PDO error.
            return null;
        }

        $sql = 'SELECT ' . self::SELECT_COLUMNS . ' FROM dtb_block '
            . 'WHERE id = :id LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => (int) $blockId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $this->hydrate($row);
    }

    #[Override]
    public function put(BlockEntity $block): void
    {
        if (! ctype_digit($block->blockId)) {
            // Defensive: a non-numeric id we cannot persist. The Fake
            // generator emits a `bk-` prefixed string; production must
            // rebind to SqlBlockIdGenerator before swapping in this
            // storage.
            return;
        }

        $id = (int) $block->blockId;

        $existsStmt = $this->pdo->prepare(
            'SELECT 1 FROM dtb_block WHERE id = :id LIMIT 1',
        );
        $existsStmt->execute([':id' => $id]);
        $exists = $existsStmt->fetchColumn() !== false;

        if ($exists) {
            $sql = 'UPDATE dtb_block SET '
                . 'block_name = :block_name, '
                . 'file_name = :file_name, '
                . 'deletable = :deletable, '
                . 'update_date = NOW() '
                . 'WHERE id = :id';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':id' => $id,
                ':block_name' => $block->blockName,
                ':file_name' => $block->blockFileName,
                ':deletable' => (int) $block->blockDeletable,
            ]);

            return;
        }

        // INSERT with explicit id. device_type_id is NULL (mtb_device_type
        // is empty in the structure-only dump so any non-NULL value
        // would raise FK 1452; the per-device variant mechanism is out
        // of scope for the BeMart admin slice). use_controller is 0
        // (plain template, no controller — no UI to enable it).
        // discriminator_type is 'block' (Doctrine single-table
        // inheritance value EC-CUBE writes).
        $sql = 'INSERT INTO dtb_block '
            . '(id, device_type_id, block_name, file_name, use_controller, '
            . 'deletable, create_date, update_date, discriminator_type) '
            . 'VALUES (:id, NULL, :block_name, :file_name, 0, '
            . ':deletable, NOW(), NOW(), :discriminator)';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':block_name' => $block->blockName,
            ':file_name' => $block->blockFileName,
            ':deletable' => (int) $block->blockDeletable,
            ':discriminator' => self::DISCRIMINATOR,
        ]);
    }

    #[Override]
    public function remove(string $blockId): void
    {
        if (! ctype_digit($blockId)) {
            // Silent no-op on a non-numeric id — same shape as the Fake
            // which `unset()`s a missing key without raising.
            return;
        }

        $id = (int) $blockId;

        // Drop any dtb_block_position placements for this block first
        // so FK_35DCD731E9ED820C (block_position.block_id → block.id)
        // does not block the row deletion. The Wave 9 admin slice never
        // INSERTs a placement row, but an externally-seeded one would
        // otherwise raise FK 1451. Idempotent — zero rows is fine.
        $stmt = $this->pdo->prepare(
            'DELETE FROM dtb_block_position WHERE block_id = :id',
        );
        $stmt->execute([':id' => $id]);

        $stmt = $this->pdo->prepare('DELETE FROM dtb_block WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(array $row): BlockEntity
    {
        return new BlockEntity(
            blockId: (string) (int) $row['id'],
            // block_name is nullable in EC-CUBE but non-null on
            // BlockEntity — coalesce NULL → '' so the projection shape
            // stays stable across externally-inserted rows.
            blockName: $row['block_name'] === null ? '' : (string) $row['block_name'],
            blockFileName: (string) $row['file_name'],
            blockDeletable: (bool) (int) $row['deletable'],
        );
    }
}
