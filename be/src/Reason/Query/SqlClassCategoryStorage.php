<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\ClassCategoryEntity;
use Override;
use PDO;

use function ctype_digit;

/**
 * Real PDO-backed ClassCategory storage — Phase 2b.
 *
 * Mirrors {@see FakeClassCategoryStorage} against the live EC-CUBE 4.3
 * schema (`dtb_class_category`). "ClassCategory" in EC-CUBE 4.x is one
 * concrete VALUE under a ClassName axis — e.g. "Red" / "Blue" under
 * "Color", or "S" / "M" / "L" under "Size" — NOT a taxonomy node (that
 * is dtb_category / SqlCategoryStorage). The table is grade A
 * (subset) — the 3-field ClassCategoryEntity projection (classCategoryId
 * / classNameId / name) lines up with EC-CUBE columns (id /
 * class_name_id / name), with one coercion at the boundary.
 *
 * Scope (Wave 7 — same as ClassCategoryEntity):
 *   The 3-field projection above. EC-CUBE has six more columns on
 *   dtb_class_category: `backend_name` (varchar(255) NULL — admin-only
 *   internal name), `sort_no` (int unsigned NOT NULL — display order,
 *   no DEFAULT), `visible` (tinyint(1) NOT NULL DEFAULT 1),
 *   `creator_id` (FK to dtb_member), `create_date`, `update_date`.
 *   None are part of ClassCategoryStorageInterface — the admin
 *   variant-value CRUD UX only edits the projected `name`. Concretely:
 *
 *     - `sort_no` is NOT NULL with no DEFAULT, so it MUST be written on
 *       INSERT. ClassCategoryEntity has no sortNo field, so on INSERT we
 *       DERIVE the next slot as MAX(sort_no)+1 (append to the end —
 *       same convention SqlClassNameStorage uses). The UPDATE branch
 *       leaves sort_no untouched (the projection never reads it, so a
 *       rename keeps the display slot).
 *     - `visible` is always written 1 — the admin slice has no
 *       show/hide UI for a variant value and ClassCategoryEntity has no
 *       visible field. NOT NULL DEFAULT 1 anyway, but written
 *       explicitly for clarity.
 *     - `backend_name` is always written NULL — ClassCategoryEntity has
 *       no backend-name field and the admin slice does not edit it. The
 *       column is nullable so NULL is valid.
 *     - `creator_id` is always written NULL — dtb_member is empty in
 *       the structure-only dump so any non-NULL value would raise FK
 *       1452 (FK_9B0D1DBA61220EA6), and ClassCategoryEntity has no
 *       creator field. Same shape SqlClassNameStorage / SqlCategoryStorage
 *       use.
 *     - `create_date` / `update_date` are maintained with NOW() (the
 *       Doctrine Timestampable behavior EC-CUBE relies on).
 *     - `class_name_id` (FK class_name_id → dtb_class_name.id,
 *       FK_9B0D1DBAB462FB2A) is part of the projection. It is written
 *       on INSERT from the entity's classNameId and is ALWAYS a valid
 *       existing axis: the ClassCategory-create Final guards
 *       `ClassNameStorage::getById($classNameId)` BEFORE calling `put`,
 *       so a bogus axis folds to a 404 before persistence is reached.
 *       The UPDATE branch leaves class_name_id untouched — a variant
 *       value never migrates between axes.
 *
 * Child product-class references on remove:
 *   `dtb_product_class` has two FKs back to dtb_class_category
 *   (class_category_id1 → FK_1A11D1BA248D128, class_category_id2 →
 *   FK_1A11D1BA9B418092). A class_category row referenced by a
 *   product_class row would raise FK 1451 on the class_category DELETE.
 *   The Wave 7 admin slice never wires a product_class to a
 *   class_category — the catalog-variation editor is out of scope — so
 *   `remove` issues a plain `DELETE FROM dtb_class_category WHERE id = ?`
 *   with NO defensive pre-clear: deleting product_class rows out from
 *   under a product is destructive and not what a variant-value delete
 *   means. The Fake `remove` simply `unset()`s the key. The contract
 *   test ({@see \MyVendor\BeMart\Tests\Resource\AdminClassCategoryResourceTest})
 *   never seeds a dtb_product_class row, so the FK is unobserved by the
 *   migration contract; an externally-seeded reference would surface
 *   the FK 1451 — by design, the contract lets it surface rather than
 *   silently cascading a destructive delete. (Contrast SqlClassNameStorage,
 *   which DOES pre-clear its child dtb_class_category rows: deleting an
 *   axis legitimately drops its values, but deleting a value must not
 *   drop the products that use it.)
 *
 * Coercions:
 *   - `id` is `int unsigned`, ClassCategoryEntity::classCategoryId is
 *     `string` → cast `(string) (int)` on read, parse with `ctype_digit`
 *     on write. A non-numeric incoming classCategoryId (e.g. the
 *     32-char hex from {@see FakeClassCategoryIdGenerator}, or the seed
 *     handle `nonexistent-zzz`) is rejected: getById returns null, put
 *     no-ops, remove no-ops. Keeps the ClassCategory Update / Delete
 *     Finals on their normal 404 path instead of raising a PDO
 *     exception — same convention as SqlClassNameStorage /
 *     SqlCategoryStorage / SqlBlockStorage.
 *   - `class_name_id` is `int unsigned`, ClassCategoryEntity::classNameId
 *     is `string` → cast `(string) (int)` on read, `(int)` on write. It
 *     is always numeric in practice (the create Final only persists
 *     after ClassNameStorage::getById resolved it — SqlClassNameStorage
 *     ids are numeric strings).
 *   - `name` is `varchar(255) NOT NULL`, matches ClassCategoryEntity::name
 *     1:1 — no coercion.
 *
 * Upsert convention (`put`):
 *   classCategoryId is pre-allocated by
 *   {@see \MyVendor\BeMart\Be\Reason\Service\SqlClassCategoryIdGenerator}
 *   before `put` is called (the ClassCategory-create Final assigns
 *   `$entity->classCategoryId` from the generator output, so the
 *   storage receives an id-bearing entity). `put` probes
 *   `SELECT 1 WHERE id = ?`; hit → UPDATE, miss → INSERT with the
 *   explicit id. discriminator_type is 'classcategory' (the value
 *   EC-CUBE writes — Doctrine single-table inheritance discriminator
 *   defaults to the lowercased class name on Eccube\Entity\ClassCategory).
 *
 * List ordering:
 *   `list()` and `listByClassName()` ORDER BY id ASC — mirrors the
 *   Fake's `ksort` on the classCategoryId key. The contract test
 *   asserts count and field presence, not order, but matching the Fake
 *   keeps the two backends behaviorally identical.
 *
 * DI is intentionally NOT wired in production (FakeClassCategoryStorage
 * remains the bound implementation). The SQL impl is exercised via the
 * test-only override in AbstractResourceSqlTestCase.
 */
final class SqlClassCategoryStorage implements ClassCategoryStorageInterface
{
    private const SELECT_COLUMNS = 'id, class_name_id, name';

    private const DISCRIMINATOR = 'classcategory';

    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    /** @return list<ClassCategoryEntity> */
    #[Override]
    public function listByClassName(string $classNameId): array
    {
        if (! ctype_digit($classNameId)) {
            // A non-numeric axis id can never match an int FK column —
            // surface as an empty scope (no rows) rather than a PDO
            // error, mirroring the Fake which simply finds no match.
            return [];
        }

        $sql = 'SELECT ' . self::SELECT_COLUMNS . ' FROM dtb_class_category '
            . 'WHERE class_name_id = :class_name_id '
            . 'ORDER BY id ASC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':class_name_id' => (int) $classNameId]);

        $out = [];
        while (($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
            $out[] = $this->hydrate($row);
        }

        return $out;
    }

    /** @return list<ClassCategoryEntity> */
    #[Override]
    public function list(): array
    {
        $sql = 'SELECT ' . self::SELECT_COLUMNS . ' FROM dtb_class_category '
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
    public function getById(string $classCategoryId): ClassCategoryEntity|null
    {
        if (! ctype_digit($classCategoryId)) {
            // Non-numeric ids (e.g. hex from FakeClassCategoryIdGenerator,
            // `nonexistent-zzz`) can never match an int PK. Surface as
            // miss so the ClassCategory Update / Delete Finals raise
            // their normal 404 instead of throwing a PDO error.
            return null;
        }

        $sql = 'SELECT ' . self::SELECT_COLUMNS . ' FROM dtb_class_category '
            . 'WHERE id = :id LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => (int) $classCategoryId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $this->hydrate($row);
    }

    #[Override]
    public function put(ClassCategoryEntity $classCategory): void
    {
        if (! ctype_digit($classCategory->classCategoryId)) {
            // Defensive: a non-numeric id we cannot persist. The Fake
            // generator emits 32-char hex; production must rebind to
            // SqlClassCategoryIdGenerator before swapping in this
            // storage.
            return;
        }

        $id = (int) $classCategory->classCategoryId;

        $existsStmt = $this->pdo->prepare(
            'SELECT 1 FROM dtb_class_category WHERE id = :id LIMIT 1',
        );
        $existsStmt->execute([':id' => $id]);
        $exists = $existsStmt->fetchColumn() !== false;

        if ($exists) {
            // UPDATE leaves sort_no, visible and class_name_id
            // untouched — the projection never reads sort_no / visible,
            // and a variant value never migrates between axes, so a
            // rename keeps the display slot and the axis pin.
            $sql = 'UPDATE dtb_class_category SET '
                . 'name = :name, '
                . 'update_date = NOW() '
                . 'WHERE id = :id';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':id' => $id,
                ':name' => $classCategory->name,
            ]);

            return;
        }

        // INSERT with explicit id. class_name_id is written from the
        // entity (always a valid existing axis — the create Final
        // guards ClassNameStorage::getById before put). backend_name is
        // NULL (nullable; the admin slice has no UI for the optional
        // "管理名" field). creator_id is NULL (dtb_member is empty in
        // the structure-only dump so any non-NULL value would raise FK
        // 1452; ClassCategoryEntity has no creator field anyway).
        // visible is 1 (no show/hide UI). sort_no is NOT NULL with no
        // DEFAULT — derive the next append slot as MAX(sort_no)+1.
        // discriminator_type is 'classcategory'.
        $sortNo = $this->nextSortNo();

        $sql = 'INSERT INTO dtb_class_category '
            . '(id, class_name_id, creator_id, backend_name, name, '
            . 'sort_no, visible, create_date, update_date, '
            . 'discriminator_type) '
            . 'VALUES (:id, :class_name_id, NULL, NULL, :name, '
            . ':sort_no, 1, NOW(), NOW(), :discriminator)';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':class_name_id' => (int) $classCategory->classNameId,
            ':name' => $classCategory->name,
            ':sort_no' => $sortNo,
            ':discriminator' => self::DISCRIMINATOR,
        ]);
    }

    #[Override]
    public function remove(string $classCategoryId): void
    {
        if (! ctype_digit($classCategoryId)) {
            // Silent no-op on a non-numeric id — same shape as the Fake
            // which `unset()`s a missing key without raising.
            return;
        }

        $id = (int) $classCategoryId;

        // Plain DELETE — no defensive pre-clear of dtb_product_class.
        // The Wave 7 admin slice never wires a product_class to a
        // class_category, so the FK (class_category_id1/2 →
        // dtb_class_category.id) is unobserved by the migration
        // contract. Unlike SqlClassNameStorage (which pre-clears its
        // child dtb_class_category rows because deleting an axis
        // legitimately drops its values), deleting a single variant
        // value must NOT cascade-delete the products that use it — so
        // an externally-seeded product_class reference is allowed to
        // surface FK 1451 rather than be silently destroyed.
        $stmt = $this->pdo->prepare(
            'DELETE FROM dtb_class_category WHERE id = :id',
        );
        $stmt->execute([':id' => $id]);
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
            'SELECT IFNULL(MAX(sort_no), 0) + 1 AS next_sort '
            . 'FROM dtb_class_category',
        );
        $stmt->execute();
        $next = $stmt->fetchColumn();

        return $next === false ? 1 : (int) $next;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(array $row): ClassCategoryEntity
    {
        return new ClassCategoryEntity(
            classCategoryId: (string) (int) $row['id'],
            classNameId: (string) (int) $row['class_name_id'],
            name: (string) $row['name'],
        );
    }
}
