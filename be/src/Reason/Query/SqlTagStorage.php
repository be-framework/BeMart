<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\TagEntity;
use Override;
use PDO;

use function ctype_digit;

/**
 * Real PDO-backed Tag storage — Phase 2b.
 *
 * Mirrors {@see FakeTagStorage} against the live EC-CUBE 4.3 schema
 * (`dtb_tag`). The table is grade A — only four columns
 * (id / name / sort_no / discriminator_type), no FK constraints, no
 * UNIQUE indexes, no timestamps. TagEntity already lines up 1:1 with
 * the columns it cares about (tagId / tagName); sort_no and
 * discriminator_type are storage-only and not carried through the
 * Entity.
 *
 * Coercions:
 *   - `id` is `int unsigned`, TagEntity::tagId is `string`
 *     → cast `(string) (int)` on read, parse with `ctype_digit` on
 *     write. A non-numeric incoming tagId (e.g. the Fake seeds
 *     `tg-new` / `tg-sale`, or a leftover hex from
 *     {@see FakeTagIdGenerator}) is rejected: getById returns null,
 *     put no-ops, remove no-ops. This keeps {@see TagDeleted} on its
 *     normal 404 path instead of raising a PDO exception, which is
 *     the same behavior the Fake exhibits when the id is absent.
 *   - `sort_no` is NOT NULL on dtb_tag but absent from TagEntity. We
 *     fix it to 0 on INSERT (Wave 9 has no ordering UX) and leave it
 *     alone on UPDATE.
 *
 * Upsert convention (`put`):
 *   tagId is pre-allocated by {@see SqlTagIdGenerator} before `put`
 *   is called (TagCreated assigns `$entity->tagId` from the generator
 *   output, so the storage receives an id-bearing entity).
 *   `put` probes `SELECT 1 WHERE id = ?`; hit → UPDATE, miss → INSERT
 *   with the explicit id. discriminator_type is 'tag' (the value
 *   EC-CUBE writes — Doctrine single-table inheritance discriminator).
 *
 * dtb_product_tag (the M:N relation table between products and tags)
 * is NOT touched here; the Wave 9 admin slice only exposes the tag
 * row CRUD, and TagDeleted is documented as idempotent without
 * cascade. Production cutover that adds tag→product detachment would
 * extend this storage; the contract test does not require it today.
 *
 * DI is intentionally NOT wired in Phase 2b; FakeTagStorage remains
 * the production-bound implementation. The SQL impl is exercised via
 * the test-only override in AbstractResourceSqlTestCase.
 */
final class SqlTagStorage implements TagStorageInterface
{
    private const SELECT_COLUMNS = 'id, name';

    private const DISCRIMINATOR = 'tag';

    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    /** @return list<TagEntity> */
    #[Override]
    public function list(): array
    {
        $sql = 'SELECT ' . self::SELECT_COLUMNS . ' FROM dtb_tag ORDER BY id ASC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        $out = [];
        while (($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
            $out[] = $this->hydrate($row);
        }

        return $out;
    }

    #[Override]
    public function getById(string $tagId): TagEntity|null
    {
        if (! ctype_digit($tagId)) {
            // Non-numeric ids (e.g. the Fake seeds `tg-new` / `tg-sale`
            // or hex from FakeTagIdGenerator) can never match an int PK.
            // Surface as miss so TagDeleted raises its normal 404
            // instead of throwing a PDO exception.
            return null;
        }

        $sql = 'SELECT ' . self::SELECT_COLUMNS . ' FROM dtb_tag '
            . 'WHERE id = :id LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => (int) $tagId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $this->hydrate($row);
    }

    #[Override]
    public function put(TagEntity $tag): void
    {
        if (! ctype_digit($tag->tagId)) {
            // Defensive: a non-numeric id we cannot persist. The Fake
            // generator emits a `tg-` prefixed string; production must
            // rebind to SqlTagIdGenerator before swapping in this
            // storage.
            return;
        }

        $id = (int) $tag->tagId;

        $existsStmt = $this->pdo->prepare(
            'SELECT 1 FROM dtb_tag WHERE id = :id LIMIT 1',
        );
        $existsStmt->execute([':id' => $id]);
        $exists = $existsStmt->fetchColumn() !== false;

        if ($exists) {
            $sql = 'UPDATE dtb_tag SET name = :name WHERE id = :id';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':id' => $id,
                ':name' => $tag->tagName,
            ]);

            return;
        }

        $sql = 'INSERT INTO dtb_tag (id, name, sort_no, discriminator_type) '
            . 'VALUES (:id, :name, :sort_no, :discriminator)';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':name' => $tag->tagName,
            ':sort_no' => 0,
            ':discriminator' => self::DISCRIMINATOR,
        ]);
    }

    #[Override]
    public function remove(string $tagId): void
    {
        if (! ctype_digit($tagId)) {
            // Silent no-op on a non-numeric id — same shape as the
            // Fake which `unset()`s a missing key without raising.
            return;
        }

        $stmt = $this->pdo->prepare('DELETE FROM dtb_tag WHERE id = :id');
        $stmt->execute([':id' => (int) $tagId]);
    }

    #[Override]
    public function reorder(string $tagId, int $sortNo): void
    {
        if (! ctype_digit($tagId)) {
            // Silent no-op on a non-numeric id — same shape as the Fake
            // which no-ops on a missing key.
            return;
        }

        // Generic `doSortNoMove` — rewrite the `sort_no` column directly.
        // dtb_tag has no timestamp columns, so this is the whole write.
        $stmt = $this->pdo->prepare(
            'UPDATE dtb_tag SET sort_no = :sort_no WHERE id = :id',
        );
        $stmt->execute([
            ':id' => (int) $tagId,
            ':sort_no' => $sortNo,
        ]);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(array $row): TagEntity
    {
        return new TagEntity(
            tagId: (string) (int) $row['id'],
            tagName: (string) $row['name'],
        );
    }
}
