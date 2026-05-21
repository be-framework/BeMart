<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\DeliveryEntity;
use Override;
use PDO;

use function ctype_digit;

/**
 * Real PDO-backed Delivery storage — Phase 2b.
 *
 * Mirrors {@see FakeDeliveryStorage} against the live EC-CUBE 4.3
 * schema (`dtb_delivery`). After the 厳密移植 narrowing
 * (Delivery Phase A), the 3-field DeliveryEntity projection
 * (deliveryId / deliveryName / visible) lines up 1:1 with the modeled
 * dtb_delivery columns (id / name / visible) — no fee columns are
 * touched: dtb_delivery has none. The per-prefecture base fee is
 * dtb_delivery_fee data and the global free-shipping threshold is
 * dtb_base_info.delivery_free_amount; both are deferred to a later
 * phase (separate models).
 *
 * Scope (Wave 9θ — same as DeliveryEntity):
 *   The 3-field projection above. dtb_delivery has eight more columns
 *   (creator_id, sale_type_id, service_name, description, confirm_url,
 *   sort_no, create_date, update_date) plus a discriminator. None are
 *   part of DeliveryStorageInterface — the admin flat-list / CRUD UX
 *   only edits name + visible. On INSERT they are written as:
 *     - creator_id   NULL (dtb_member is empty in the structure-only
 *       dump so any non-NULL value would raise FK 1452 — same shape as
 *       SqlCategoryStorage / SqlBlockStorage)
 *     - sale_type_id NULL (DeliveryEntity carries no sale-type axis;
 *       mtb_sale_type is empty in the structure-only dump so a non-NULL
 *       value would raise FK 1452. A consumer that needs a real
 *       sale_type can seed the master via SqlFixturesTrait::seedSaleTypes
 *       and the column would round-trip — but the BeMart slice never
 *       projects it, so NULL is the honest default)
 *     - service_name / description / confirm_url / sort_no  NULL
 *       (no UI in the BeMart admin slice)
 *     - create_date / update_date  NOW() (same Doctrine Timestampable
 *       behavior SqlPageStorage / SqlBlockStorage mimic)
 *     - discriminator_type  'delivery' (Doctrine single-table
 *       inheritance value EC-CUBE writes on Eccube\Entity\Delivery)
 *
 * Coercions:
 *   - `id` is `int unsigned`, DeliveryEntity::deliveryId is `string`
 *     → cast `(string) (int)` on read, parse with `ctype_digit` on
 *     write. A non-numeric incoming deliveryId (e.g. 32-char hex from
 *     {@see FakeDeliveryIdGenerator}) is rejected: getById returns
 *     null, put no-ops, remove no-ops. Keeps {@see DeliveryDeleted} /
 *     {@see DeliveryUpdated} on their normal 404 path instead of
 *     raising a PDO exception — same convention as SqlBlockStorage /
 *     SqlPageStorage / SqlNewsStorage.
 *   - `name` is nullable in EC-CUBE but non-null on DeliveryEntity.
 *     Hydrator coerces NULL → '' so the projection's non-null shape is
 *     preserved across externally-inserted rows.
 *   - `visible` is `tinyint(1) NOT NULL DEFAULT 1`,
 *     DeliveryEntity::visible is `bool` → cast `(bool) (int)` on read,
 *     `(int)` on write.
 *
 * Upsert convention (`put`):
 *   deliveryId is pre-allocated by {@see SqlDeliveryIdGenerator} before
 *   `put` is called (DeliveryCreated assigns `$entity->deliveryId` from
 *   the generator output, so the storage receives an id-bearing
 *   entity). `put` probes `SELECT 1 WHERE id = ?`; hit → UPDATE, miss →
 *   INSERT with the explicit id. ALPS defines `doUpdateDelivery`
 *   (DeliveryUpdated merges + puts on the same id), so the UPDATE
 *   branch is actively exercised.
 *
 * List ordering: `ORDER BY id ASC` — the contract test asserts count
 * and presence, not order. Same parity convention as SqlBlockStorage /
 * SqlPageStorage / SqlPaymentMethodAdminStorage.
 *
 * DI is intentionally NOT wired in production (FakeDeliveryStorage
 * remains the bound implementation). The SQL impl is exercised via the
 * test-only override in AbstractResourceSqlTestCase.
 */
final class SqlDeliveryStorage implements DeliveryStorageInterface
{
    private const SELECT_COLUMNS = 'id, name, visible';

    private const DISCRIMINATOR = 'delivery';

    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    /** @return list<DeliveryEntity> */
    #[Override]
    public function list(): array
    {
        $sql = 'SELECT ' . self::SELECT_COLUMNS . ' FROM dtb_delivery '
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
    public function getById(string $deliveryId): DeliveryEntity|null
    {
        if (! ctype_digit($deliveryId)) {
            // Non-numeric ids (e.g. 32-char hex from FakeDeliveryIdGenerator)
            // can never match an int PK. Surface as miss so
            // DeliveryUpdated / DeliveryDeleted raise their normal 404.
            return null;
        }

        $sql = 'SELECT ' . self::SELECT_COLUMNS . ' FROM dtb_delivery '
            . 'WHERE id = :id LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => (int) $deliveryId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $this->hydrate($row);
    }

    #[Override]
    public function put(DeliveryEntity $delivery): void
    {
        if (! ctype_digit($delivery->deliveryId)) {
            // Defensive: a non-numeric id we cannot persist. The Fake
            // generator emits 32-char hex; production must rebind to
            // SqlDeliveryIdGenerator before swapping in this storage.
            return;
        }

        $id = (int) $delivery->deliveryId;

        $existsStmt = $this->pdo->prepare(
            'SELECT 1 FROM dtb_delivery WHERE id = :id LIMIT 1',
        );
        $existsStmt->execute([':id' => $id]);
        $exists = $existsStmt->fetchColumn() !== false;

        if ($exists) {
            $sql = 'UPDATE dtb_delivery SET '
                . 'name = :name, '
                . 'visible = :visible, '
                . 'update_date = NOW() '
                . 'WHERE id = :id';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':id' => $id,
                ':name' => $delivery->deliveryName,
                ':visible' => (int) $delivery->visible,
            ]);

            return;
        }

        // INSERT with explicit id. creator_id / sale_type_id are NULL
        // (dtb_member and mtb_sale_type are empty in the structure-only
        // dump so a non-NULL value would raise FK 1452; the BeMart
        // slice projects neither). service_name / description /
        // confirm_url / sort_no are NULL (no UI). discriminator_type is
        // 'delivery' (Doctrine single-table inheritance value EC-CUBE
        // writes).
        $sql = 'INSERT INTO dtb_delivery '
            . '(id, creator_id, sale_type_id, name, service_name, '
            . 'description, confirm_url, sort_no, visible, '
            . 'create_date, update_date, discriminator_type) '
            . 'VALUES (:id, NULL, NULL, :name, NULL, '
            . 'NULL, NULL, NULL, :visible, '
            . 'NOW(), NOW(), :discriminator)';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':name' => $delivery->deliveryName,
            ':visible' => (int) $delivery->visible,
            ':discriminator' => self::DISCRIMINATOR,
        ]);
    }

    #[Override]
    public function remove(string $deliveryId): void
    {
        if (! ctype_digit($deliveryId)) {
            // Silent no-op on a non-numeric id — same shape as the Fake
            // which `unset()`s a missing key without raising.
            return;
        }

        $stmt = $this->pdo->prepare('DELETE FROM dtb_delivery WHERE id = :id');
        $stmt->execute([':id' => (int) $deliveryId]);
    }

    #[Override]
    public function reorder(string $deliveryId, int $sortNo): void
    {
        if (! ctype_digit($deliveryId)) {
            // Silent no-op on a non-numeric id — same shape as the Fake.
            return;
        }

        // Generic `doSortNoMove` — rewrite the `sort_no` column
        // (smallint unsigned, nullable) directly.
        $stmt = $this->pdo->prepare(
            'UPDATE dtb_delivery SET sort_no = :sort_no, update_date = NOW() '
            . 'WHERE id = :id',
        );
        $stmt->execute([
            ':id' => (int) $deliveryId,
            ':sort_no' => $sortNo,
        ]);
    }

    #[Override]
    public function setVisible(string $deliveryId, bool $visible): void
    {
        if (! ctype_digit($deliveryId)) {
            // Silent no-op on a non-numeric id — same shape as the Fake.
            return;
        }

        // Generic `doToggleVisible` — rewrite the `visible` column
        // (tinyint(1) NOT NULL DEFAULT 1). `visible` IS part of the
        // DeliveryEntity projection, so a subsequent read reflects it.
        $stmt = $this->pdo->prepare(
            'UPDATE dtb_delivery SET visible = :visible, update_date = NOW() '
            . 'WHERE id = :id',
        );
        $stmt->execute([
            ':id' => (int) $deliveryId,
            ':visible' => (int) $visible,
        ]);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(array $row): DeliveryEntity
    {
        return new DeliveryEntity(
            deliveryId: (string) (int) $row['id'],
            // name is nullable in EC-CUBE but non-null on DeliveryEntity
            // — coalesce NULL → '' so the projection shape stays stable
            // across externally-inserted rows.
            deliveryName: $row['name'] === null ? '' : (string) $row['name'],
            visible: (bool) (int) $row['visible'],
        );
    }
}
