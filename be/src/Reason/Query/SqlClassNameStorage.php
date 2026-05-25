<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\ClassNameEntity;
use Override;
use PDO;

use function ctype_digit;

/**
 * Real PDO-backed ClassName storage — Phase 2b.
 *
 * Mirrors {@see FakeClassNameStorage} against the live EC-CUBE 4.3
 * schema (`dtb_class_name`). "ClassName" in EC-CUBE 4.x is the AXIS of
 * a product variant — e.g. "Color" or "Size" — NOT an OOP class; the
 * concrete values along an axis live in dtb_class_category. The table
 * is grade A (subset) — the 2-field ClassNameEntity projection
 * (classNameId / name) lines up 1:1 with EC-CUBE columns (id / name),
 * with one coercion at the boundary.
 *
 * Scope (Wave 7 — same as ClassNameEntity):
 *   The 2-field projection above. EC-CUBE has five more columns on
 *   dtb_class_name: `backend_name` (varchar(255) NULL — the admin-only
 *   internal name), `sort_no` (int unsigned NOT NULL — display order),
 *   `creator_id` (FK to dtb_member), `create_date`, `update_date`.
 *   None are part of ClassNameStorageInterface — the admin axis-list /
 *   CRUD UX only edits the projected `name`. Concretely:
 *
 *     - `sort_no` is NOT NULL with no DEFAULT, so it MUST be written on
 *       INSERT. ClassNameEntity has no sortNo field, so on INSERT we
 *       DERIVE the next slot as MAX(sort_no)+1 (append to the end —
 *       same convention SqlClassCategory-style tables would use). The
 *       UPDATE branch leaves sort_no untouched (the projection never
 *       reads or writes it).
 *     - `backend_name` is always written NULL — ClassNameEntity has no
 *       backend-name field and the admin slice does not edit it. The
 *       column is nullable so NULL is valid (EC-CUBE itself leaves it
 *       NULL unless the operator fills the optional "管理名" field).
 *     - `creator_id` is always written NULL — dtb_member is empty in
 *       the structure-only dump so any non-NULL value would raise FK
 *       1452 (FK_187C95AD61220EA6), and ClassNameEntity has no creator
 *       field. Same shape SqlCategoryStorage / SqlBlockStorage use.
 *     - `create_date` / `update_date` are maintained with NOW() (the
 *       Doctrine Timestampable behavior EC-CUBE relies on — same shape
 *       SqlCategoryStorage / SqlBlockStorage mimic).
 *
 * Child class-category cascade on remove:
 *   `dtb_class_category` (the axis VALUES — e.g. "Red"/"Blue" under
 *   "Color") has an FK class_name_id → dtb_class_name.id
 *   (FK_9B0D1DBAB462FB2A). A class_name with child class-category rows
 *   would raise FK 1451 on the class_name DELETE. The Wave 7 admin
 *   slice never writes a dtb_class_category row, but an externally-
 *   seeded one would block deletion — so `remove` issues a defensive
 *   `DELETE FROM dtb_class_category WHERE class_name_id = ?` first.
 *   Same shape SqlCategoryStorage uses against dtb_product_category and
 *   SqlBlockStorage against dtb_block_position. The Fake `remove`
 *   simply `unset()`s the key with no notion of children, so this
 *   pre-clear keeps the SQL side behaviorally identical (a class_name
 *   delete always succeeds) — the contract test never seeds a child
 *   class_category, so the cascade is unobserved by the hypermedia
 *   contract but covered by the unit test.
 *
 * Coercions:
 *   - `id` is `int unsigned`, ClassNameEntity::classNameId is `string`
 *     → cast `(string) (int)` on read, parse with `ctype_digit` on
 *     write. A non-numeric incoming classNameId (e.g. the 32-char hex
 *     from {@see FakeClassNameIdGenerator}, or the seed handle
 *     `nonexistent-zzz`) is rejected: getById returns null, put
 *     no-ops, remove no-ops. Keeps the ClassName Update / Delete Finals
 *     on their normal 404 path instead of raising a PDO exception —
 *     same convention as SqlCategoryStorage / SqlBlockStorage /
 *     SqlPageStorage / SqlTagStorage.
 *   - `name` is `varchar(255) NOT NULL`, matches ClassNameEntity::name
 *     1:1 — no coercion.
 *
 * Upsert convention (`put`):
 *   classNameId is pre-allocated by {@see SqlClassNameIdGenerator}
 *   before `put` is called (the ClassName-create Final assigns
 *   `$entity->classNameId` from the generator output, so the storage
 *   receives an id-bearing entity). `put` probes `SELECT 1 WHERE id = ?`;
 *   hit → UPDATE, miss → INSERT with the explicit id.
 *   discriminator_type is 'classname' (the value EC-CUBE writes —
 *   Doctrine single-table inheritance discriminator defaults to the
 *   lowercased class name on Eccube\Entity\ClassName).
 *
 * List ordering: `ORDER BY id ASC` — mirrors the Fake's `ksort` on the
 * classNameId key (the Fake docstring: "sorted by id for stable
 * display"). The contract test asserts count and field presence, not
 * order, but matching the Fake keeps the two backends behaviorally
 * identical.
 *
 * DI is intentionally NOT wired in production (FakeClassNameStorage
 * remains the bound implementation). The SQL impl is exercised via the
 * test-only override in AbstractResourceSqlTestCase.
 */
final class SqlClassNameStorage implements ClassNameStorageInterface
{
    private const SELECT_COLUMNS = 'id, name';

    private const DISCRIMINATOR = 'classname';

    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    /** @return list<ClassNameEntity> */
    #[Override]
    public function list(): array
    {
        $sql = 'SELECT ' . self::SELECT_COLUMNS . ' FROM dtb_class_name '
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
    public function getById(string $classNameId): ClassNameEntity|null
    {
        if (! ctype_digit($classNameId)) {
            // Non-numeric ids (e.g. hex from FakeClassNameIdGenerator,
            // `nonexistent-zzz`) can never match an int PK. Surface as
            // miss so the ClassName Update / Delete Finals raise their
            // normal 404 instead of throwing a PDO error.
            return null;
        }

        $sql = 'SELECT ' . self::SELECT_COLUMNS . ' FROM dtb_class_name '
            . 'WHERE id = :id LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => (int) $classNameId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $this->hydrate($row);
    }

    #[Override]
    public function put(ClassNameEntity $className): void
    {
        if (! ctype_digit($className->classNameId)) {
            // Defensive: a non-numeric id we cannot persist. The Fake
            // generator emits 32-char hex; production must rebind to
            // SqlClassNameIdGenerator before swapping in this storage.
            return;
        }

        $id = (int) $className->classNameId;

        $existsStmt = $this->pdo->prepare(
            'SELECT 1 FROM dtb_class_name WHERE id = :id LIMIT 1',
        );
        $existsStmt->execute([':id' => $id]);
        $exists = $existsStmt->fetchColumn() !== false;

        if ($exists) {
            // UPDATE leaves sort_no untouched — the projection never
            // reads or writes it, so a rename keeps the display slot.
            $sql = 'UPDATE dtb_class_name SET '
                . 'name = :name, '
                . 'update_date = NOW() '
                . 'WHERE id = :id';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':id' => $id,
                ':name' => $className->name,
            ]);

            return;
        }

        // INSERT with explicit id. backend_name is NULL (nullable; the
        // admin slice has no UI for the optional "管理名" field).
        // creator_id is NULL (dtb_member is empty in the structure-only
        // dump so any non-NULL value would raise FK 1452;
        // ClassNameEntity has no creator field anyway). sort_no is NOT
        // NULL with no DEFAULT — derive the next append slot as
        // MAX(sort_no)+1. discriminator_type is 'classname' (Doctrine
        // single-table inheritance value EC-CUBE writes).
        $sortNo = $this->nextSortNo();

        $sql = 'INSERT INTO dtb_class_name '
            . '(id, creator_id, backend_name, name, sort_no, '
            . 'create_date, update_date, discriminator_type) '
            . 'VALUES (:id, NULL, NULL, :name, :sort_no, '
            . 'NOW(), NOW(), :discriminator)';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':name' => $className->name,
            ':sort_no' => $sortNo,
            ':discriminator' => self::DISCRIMINATOR,
        ]);
    }

    #[Override]
    public function remove(string $classNameId): void
    {
        if (! ctype_digit($classNameId)) {
            // Silent no-op on a non-numeric id — same shape as the Fake
            // which `unset()`s a missing key without raising.
            return;
        }

        $id = (int) $classNameId;

        // Drop any dtb_class_category rows (the axis VALUES) for this
        // axis first so FK_9B0D1DBAB462FB2A (class_category.class_name_id
        // → class_name.id) does not block the row deletion. The Wave 7
        // admin slice never INSERTs a class_category row, but an
        // externally-seeded one would otherwise raise FK 1451. The Fake
        // `unset()`s with no notion of children, so this pre-clear keeps
        // the SQL `remove` always-succeeds shape identical. Idempotent —
        // zero rows is fine.
        $stmt = $this->pdo->prepare(
            'DELETE FROM dtb_class_category WHERE class_name_id = :id',
        );
        $stmt->execute([':id' => $id]);

        $stmt = $this->pdo->prepare('DELETE FROM dtb_class_name WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    #[Override]
    public function reorder(string $classNameId, int $sortNo): void
    {
        if (! ctype_digit($classNameId)) {
            // Silent no-op on a non-numeric id — same shape as the Fake
            // which no-ops on a missing key.
            return;
        }

        // Generic `doSortNoMove` — rewrite the `sort_no` column directly.
        // No `update_date` bump: a drag-and-drop reorder is metadata-only
        // and EC-CUBE's *_sort_no_move routes likewise touch sort_no only.
        $stmt = $this->pdo->prepare(
            'UPDATE dtb_class_name SET sort_no = :sort_no WHERE id = :id',
        );
        $stmt->execute([
            ':id' => (int) $classNameId,
            ':sort_no' => $sortNo,
        ]);
    }

    /**
     * Derive the next append slot for `sort_no` (NOT NULL, no DEFAULT).
     * Empty table → 1; otherwise MAX(sort_no)+1. The projection never
     * reads sort_no — this just satisfies the NOT NULL constraint with
     * a stable monotonic value mirroring EC-CUBE's own append behavior.
     */
    private function nextSortNo(): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT IFNULL(MAX(sort_no), 0) + 1 AS next_sort FROM dtb_class_name',
        );
        $stmt->execute();
        $next = $stmt->fetchColumn();

        return $next === false ? 1 : (int) $next;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(array $row): ClassNameEntity
    {
        return new ClassNameEntity(
            classNameId: (string) (int) $row['id'],
            name: (string) $row['name'],
        );
    }
}
