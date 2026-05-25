<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\CategoryEntity;
use Override;
use PDO;

use function ctype_digit;

/**
 * Real PDO-backed Category storage — Phase 2b.
 *
 * Mirrors {@see FakeCategoryStorage} against the live EC-CUBE 4.3
 * schema (`dtb_category`). The table is grade A — the 4-field
 * CategoryEntity projection (categoryId / categoryName / parentId /
 * sortNo) lines up 1:1 with EC-CUBE columns (id / category_name /
 * parent_category_id / sort_no), with one coercion at the boundary.
 *
 * Scope (Wave 7 — same as CategoryEntity):
 *   The 4-field projection above. EC-CUBE has four more columns on
 *   dtb_category: `hierarchy` (int unsigned NOT NULL — the depth cache
 *   "root=1, child=2…"), `creator_id` (FK to dtb_member), `create_date`,
 *   `update_date`. None are part of CategoryStorageInterface — the
 *   admin flat-list / CRUD UX only edits the 4 projected fields, and
 *   nested-children projection is deliberately Phase 2 scope (the
 *   CategoryEntity docstring already calls this out). Concretely:
 *
 *     - `hierarchy` is NOT NULL with no DEFAULT, so it MUST be written
 *       on INSERT. We DERIVE it from the parent: a root category
 *       (parent_category_id NULL) is hierarchy 1; a child is
 *       parent.hierarchy + 1. The probe runs once on INSERT and on
 *       UPDATE (parentId can change), so the depth cache stays
 *       consistent with what EC-CUBE's CategoryRepository would
 *       compute. Re-parenting a subtree does NOT cascade hierarchy
 *       down to grandchildren — that is out of scope for the flat
 *       admin slice (EC-CUBE's own move operation rewrites the whole
 *       subtree; BeMart's admin only edits one node at a time and the
 *       projection never reads `hierarchy`).
 *     - `creator_id` is always written NULL — dtb_member is empty in
 *       the structure-only dump so any non-NULL value would raise FK
 *       1452 (FK_5ED2C2B61220EA6), and CategoryEntity has no creator
 *       field. Same shape SqlNewsStorage / SqlTaxRuleStorage use.
 *     - `create_date` / `update_date` are maintained with NOW() (the
 *       Doctrine Timestampable behavior EC-CUBE relies on — same shape
 *       SqlPageStorage / SqlBlockStorage mimic).
 *
 * Self-referential parent FK (FK_5ED2C2B796A8F92,
 * parent_category_id → dtb_category.id):
 *   A child row cannot be INSERTed before its parent exists. The Be
 *   layer enforces this UPSTREAM — {@see CategoryCreated} probes
 *   `getById(parentId)` and raises CategoryNotFoundException (404)
 *   before calling `put`, so the storage only ever receives a
 *   parentId that already resolves to a row (or NULL). The hierarchy
 *   derivation below re-reads the parent row defensively; if the
 *   parent is somehow absent it falls back to hierarchy 1 rather than
 *   raising, keeping the storage a thin persistence adapter.
 *
 * Product-category cascade on remove:
 *   `dtb_product_category` (composite-PK product_id+category_id) is
 *   the join table assigning products to a category, with an FK
 *   category_id → dtb_category.id (FK_B057789112469DE2). A category
 *   with assigned products would raise FK 1451 on the category
 *   DELETE. The Wave 7 admin slice never writes a dtb_product_category
 *   row, but an externally-seeded one would block deletion — so
 *   `remove` issues a defensive
 *   `DELETE FROM dtb_product_category WHERE category_id = ?` first.
 *   Same shape SqlBlockStorage uses against dtb_block_position.
 *
 *   Child categories are NOT pre-cleared: deleting a parent that still
 *   has children would raise FK 1451 on the self-FK, which is the
 *   correct EC-CUBE behavior (a non-empty parent cannot be dropped).
 *   The Fake exhibits the same — `unset()` of a parent leaves orphaned
 *   children referencing a missing id; the SQL side simply surfaces
 *   the constraint. The contract test never deletes a parent with
 *   children, so this divergence is unobserved by the hypermedia
 *   contract.
 *
 * Coercions:
 *   - `id` is `int unsigned`, CategoryEntity::categoryId is `string`
 *     → cast `(string) (int)` on read, parse with `ctype_digit` on
 *     write. A non-numeric incoming categoryId (e.g. the 32-char hex
 *     from {@see FakeCategoryIdGenerator}) is rejected: getById
 *     returns null, put no-ops, remove no-ops. Keeps
 *     {@see CategoryUpdated} / {@see CategoryDeleted} on their normal
 *     404 path instead of raising a PDO exception — same convention
 *     as SqlBlockStorage / SqlPageStorage / SqlNewsStorage /
 *     SqlTagStorage / SqlTaxRuleStorage. `parent_category_id` is
 *     coerced the same way: a non-numeric incoming parentId is stored
 *     as NULL (root) since it can never reference a real row.
 *   - `category_name` is `varchar(255) NOT NULL`, matches
 *     CategoryEntity::categoryName 1:1 — no coercion.
 *   - `sort_no` is `int(11) NOT NULL`, CategoryEntity::sortNo is `int`
 *     → `(int)` cast on read.
 *
 * Upsert convention (`put`):
 *   categoryId is pre-allocated by {@see SqlCategoryIdGenerator}
 *   before `put` is called (CategoryCreated assigns
 *   `$entity->categoryId` from the generator output, so the storage
 *   receives an id-bearing entity). `put` probes
 *   `SELECT 1 WHERE id = ?`; hit → UPDATE, miss → INSERT with the
 *   explicit id. discriminator_type is 'category' (the value EC-CUBE
 *   writes — Doctrine single-table inheritance discriminator defaults
 *   to the lowercased class name on Eccube\Entity\Category).
 *
 *   ALPS defines `doUpdateCategory` (CategoryUpdated Final merges +
 *   put on the same id), so the UPDATE branch is actively exercised,
 *   same as the News / Page / Block flows.
 *
 * List ordering: `ORDER BY sort_no ASC, id ASC` — mirrors the Fake's
 * (sortNo asc, categoryId asc) projection so a caller can rely on a
 * stable display order without re-sorting. The contract test asserts
 * count and field presence, not order, but matching the Fake keeps
 * the two backends behaviorally identical.
 *
 * DI is intentionally NOT wired in production (FakeCategoryStorage
 * remains the bound implementation). The SQL impl is exercised via
 * the test-only override in AbstractResourceSqlTestCase.
 */
final class SqlCategoryStorage implements CategoryStorageInterface
{
    private const SELECT_COLUMNS = 'id, category_name, parent_category_id, sort_no';

    private const DISCRIMINATOR = 'category';

    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    /** @return list<CategoryEntity> */
    #[Override]
    public function list(): array
    {
        $sql = 'SELECT ' . self::SELECT_COLUMNS . ' FROM dtb_category '
            . 'ORDER BY sort_no ASC, id ASC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        $out = [];
        while (($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
            $out[] = $this->hydrate($row);
        }

        return $out;
    }

    #[Override]
    public function getById(string $categoryId): CategoryEntity|null
    {
        if (! ctype_digit($categoryId)) {
            // Non-numeric ids (e.g. hex from FakeCategoryIdGenerator,
            // `nonexistent-zzz`) can never match an int PK. Surface as
            // miss so CategoryUpdated / CategoryDeleted raise their
            // normal 404 instead of throwing a PDO error.
            return null;
        }

        $sql = 'SELECT ' . self::SELECT_COLUMNS . ' FROM dtb_category '
            . 'WHERE id = :id LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => (int) $categoryId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $this->hydrate($row);
    }

    #[Override]
    public function put(CategoryEntity $category): void
    {
        if (! ctype_digit($category->categoryId)) {
            // Defensive: a non-numeric id we cannot persist. The Fake
            // generator emits 32-char hex; production must rebind to
            // SqlCategoryIdGenerator before swapping in this storage.
            return;
        }

        $id = (int) $category->categoryId;

        // A non-numeric parentId can never reference a real int PK —
        // fold to NULL (root). A numeric parentId is kept as-is; the
        // Be layer (CategoryCreated) has already verified it resolves.
        $parentId = ($category->parentId !== null && ctype_digit($category->parentId))
            ? (int) $category->parentId
            : null;

        // Derive the depth cache from the parent. Root → 1, child →
        // parent.hierarchy + 1. A missing parent falls back to 1
        // rather than raising — the storage stays a thin adapter.
        $hierarchy = $this->resolveHierarchy($parentId);

        $existsStmt = $this->pdo->prepare(
            'SELECT 1 FROM dtb_category WHERE id = :id LIMIT 1',
        );
        $existsStmt->execute([':id' => $id]);
        $exists = $existsStmt->fetchColumn() !== false;

        if ($exists) {
            $sql = 'UPDATE dtb_category SET '
                . 'category_name = :category_name, '
                . 'parent_category_id = :parent_category_id, '
                . 'hierarchy = :hierarchy, '
                . 'sort_no = :sort_no, '
                . 'update_date = NOW() '
                . 'WHERE id = :id';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':id' => $id,
                ':category_name' => $category->categoryName,
                ':parent_category_id' => $parentId,
                ':hierarchy' => $hierarchy,
                ':sort_no' => $category->sortNo,
            ]);

            return;
        }

        // INSERT with explicit id. creator_id is NULL (dtb_member is
        // empty in the structure-only dump so any non-NULL value would
        // raise FK 1452; CategoryEntity has no creator field anyway).
        // discriminator_type is 'category' (Doctrine single-table
        // inheritance value EC-CUBE writes).
        $sql = 'INSERT INTO dtb_category '
            . '(id, parent_category_id, creator_id, category_name, '
            . 'hierarchy, sort_no, create_date, update_date, discriminator_type) '
            . 'VALUES (:id, :parent_category_id, NULL, :category_name, '
            . ':hierarchy, :sort_no, NOW(), NOW(), :discriminator)';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':parent_category_id' => $parentId,
            ':category_name' => $category->categoryName,
            ':hierarchy' => $hierarchy,
            ':sort_no' => $category->sortNo,
            ':discriminator' => self::DISCRIMINATOR,
        ]);
    }

    #[Override]
    public function remove(string $categoryId): void
    {
        if (! ctype_digit($categoryId)) {
            // Silent no-op on a non-numeric id — same shape as the Fake
            // which `unset()`s a missing key without raising.
            return;
        }

        $id = (int) $categoryId;

        // Drop any dtb_product_category assignments for this category
        // first so FK_B057789112469DE2 (product_category.category_id →
        // category.id) does not block the row deletion. The Wave 7
        // admin slice never INSERTs an assignment row, but an
        // externally-seeded one would otherwise raise FK 1451.
        // Idempotent — zero rows is fine.
        $stmt = $this->pdo->prepare(
            'DELETE FROM dtb_product_category WHERE category_id = :id',
        );
        $stmt->execute([':id' => $id]);

        // Child categories are intentionally NOT pre-cleared: a parent
        // with children must not be silently dropped (the self-FK
        // raising 1451 is the correct EC-CUBE behavior). The contract
        // test never deletes a parent that still has children.
        $stmt = $this->pdo->prepare('DELETE FROM dtb_category WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    /**
     * Derive a row's `hierarchy` depth cache from its parent.
     *
     * Root (parentId NULL) → 1. Child → parent.hierarchy + 1. A
     * parentId that no longer resolves to a row falls back to 1 — the
     * storage never raises on a stale parent, it just persists a
     * best-effort depth (the projection never reads `hierarchy`).
     */
    private function resolveHierarchy(int|null $parentId): int
    {
        if ($parentId === null) {
            return 1;
        }

        $stmt = $this->pdo->prepare(
            'SELECT hierarchy FROM dtb_category WHERE id = :id LIMIT 1',
        );
        $stmt->execute([':id' => $parentId]);
        $parentHierarchy = $stmt->fetchColumn();

        return $parentHierarchy === false ? 1 : (int) $parentHierarchy + 1;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(array $row): CategoryEntity
    {
        return new CategoryEntity(
            categoryId: (string) (int) $row['id'],
            categoryName: (string) $row['category_name'],
            // parent_category_id is nullable — keep NULL as NULL so a
            // root category projects parentId = null, otherwise
            // stringify the int handle to match categoryId's shape.
            parentId: $row['parent_category_id'] === null
                ? null
                : (string) (int) $row['parent_category_id'],
            sortNo: (int) $row['sort_no'],
        );
    }
}
