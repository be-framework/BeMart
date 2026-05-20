<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\ShippingAddressEntity;
use Override;
use PDO;

/**
 * Real PDO-backed shipping-address storage — Phase 2b.
 *
 * Mirrors {@see FakeShippingAddressStorage} against the live EC-CUBE 4.3
 * schema (`dtb_shipping`). Pure prepared statements: no Doctrine, no ORM.
 *
 * order_no ↔ order_id resolution
 * ------------------------------
 * {@see ShippingAddressStorageInterface} keys everything by `orderNo`
 * (the customer-facing string handle), but `dtb_shipping` references the
 * order through the int FK `order_id` → `dtb_order.id` — exactly the same
 * indirection {@see SqlOrderQuery::itemsByOrderNo} has for dtb_order_item.
 * Every method here therefore translates: a read resolves
 * `order_no → id` via a sub-SELECT (or a JOIN for `listAll`); a write
 * resolves it once up front. An orderNo with no matching dtb_order row is
 * an honest miss — `getByOrderNo` returns null and `put` is a silent
 * no-op. In practice the admin Finals ({@see \MyVendor\BeMart\Be\Final\AdminShippingAddressSelected}
 * / {@see \MyVendor\BeMart\Be\Final\AdminShippingAddressUpdated}) already
 * reject an unknown orderNo via {@see OrderQueryInterface} before calling
 * `put`, so the no-op is defence-in-depth, not the primary guard.
 *
 * Single-row-per-order
 * --------------------
 * `dtb_shipping` allows N rows per order (multi-ship: one row per
 * destination). BeMart's {@see ShippingAddressEntity} models the simple
 * single-shipping case — one delivery target per order. `put` enforces
 * that by probing `SELECT id WHERE order_id = ?` and UPDATEing the
 * existing row in place rather than appending a second. The 4.3 dump has
 * no UNIQUE index on `order_id`, so the probe (not a DB constraint) is
 * what keeps the invariant; documented in sql/diff/entity-vs-eccube.md.
 *
 * Entity-vs-column coercions (hydrate)
 * ------------------------------------
 *   - `name01` / `name02` are NOT NULL — direct.
 *   - `postal_code` / `addr01` / `addr02` are nullable, the Entity fields
 *     are non-null `string` → coerce NULL to empty string (same
 *     convention as {@see SqlAddressStorage::hydrate}).
 *   - `pref_id` is a nullable FK → `mtb_pref` (an EMPTY master table in
 *     the structure-only dump). {@see ShippingAddressEntity::$pref} is a
 *     non-null `int`; NULL hydrates to 0, and on write a `pref` of 0 is
 *     stored as a real NULL so the FK holds when mtb_pref has no matching
 *     row. Same empty-master precedent as SqlAddressStorage / SqlCustomerQuery.
 *
 * NOT NULL columns the Entity does not model
 * ------------------------------------------
 * `dtb_shipping` requires `create_date` / `update_date` (Timestampable)
 * and `discriminator_type` (single-table-inheritance tag). INSERT
 * supplies NOW() for the timestamps and 'shipping' for the discriminator;
 * UPDATE bumps `update_date` only. Every other column (country_id,
 * delivery_id, kana01/02, company_name, tracking_number, …) is
 * column-nullable and left untouched — BeMart's slice does not model
 * them, and an UPDATE never clears columns it does not name.
 *
 * DI is intentionally NOT wired in production (FakeShippingAddressStorage
 * remains the bound implementation). The SQL impl is exercised via the
 * test-only override in AbstractResourceSqlTestCase.
 */
final class SqlShippingAddressStorage implements ShippingAddressStorageInterface
{
    private const DISCRIMINATOR = 'shipping';

    private const SELECT_COLUMNS = 'name01, name02, postal_code, pref_id, '
        . 'addr01, addr02, phone_number';

    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    #[Override]
    public function getByOrderNo(string $orderNo): ShippingAddressEntity|null
    {
        $orderId = $this->orderIdByOrderNo($orderNo);
        if ($orderId === null) {
            return null;
        }

        $sql = 'SELECT ' . self::SELECT_COLUMNS . ' FROM dtb_shipping '
            . 'WHERE order_id = :order_id ORDER BY id ASC LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':order_id' => $orderId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $this->hydrate($orderNo, $row);
    }

    #[Override]
    public function put(ShippingAddressEntity $address): void
    {
        $orderId = $this->orderIdByOrderNo($address->orderNo);
        if ($orderId === null) {
            // No dtb_order row to attach the shipping address to. The
            // admin Finals gate on OrderQuery first, so this is the
            // defensive branch: silently no-op rather than raise, same
            // shape as the Fake (which simply keys by orderNo with no
            // existence check of its own).
            return;
        }

        $existsStmt = $this->pdo->prepare(
            'SELECT id FROM dtb_shipping WHERE order_id = :order_id '
            . 'ORDER BY id ASC LIMIT 1',
        );
        $existsStmt->execute([':order_id' => $orderId]);
        $existingId = $existsStmt->fetchColumn();

        if ($existingId !== false) {
            $this->updateRow((int) $existingId, $address);

            return;
        }

        $this->insertRow($orderId, $address);
    }

    /** @return list<ShippingAddressEntity> */
    #[Override]
    public function listAll(): array
    {
        // JOIN dtb_order to surface order_no on every row — the Entity is
        // keyed by orderNo, and a dtb_shipping row whose order_id does not
        // resolve to a dtb_order row cannot yield one, so the INNER JOIN
        // also drops orphan shipping rows (none in a well-formed dataset).
        $sql = 'SELECT o.order_no, s.name01, s.name02, s.postal_code, '
            . 's.pref_id, s.addr01, s.addr02, s.phone_number '
            . 'FROM dtb_shipping s '
            . 'INNER JOIN dtb_order o ON o.id = s.order_id '
            . 'ORDER BY s.id ASC';
        $stmt = $this->pdo->query($sql);

        $out = [];
        while (($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
            $out[] = $this->hydrate((string) $row['order_no'], $row);
        }

        return $out;
    }

    /**
     * Resolve a customer-facing order_no to its dtb_order surrogate id.
     * Returns null when no such order exists.
     */
    private function orderIdByOrderNo(string $orderNo): int|null
    {
        $stmt = $this->pdo->prepare(
            'SELECT id FROM dtb_order WHERE order_no = :order_no LIMIT 1',
        );
        $stmt->execute([':order_no' => $orderNo]);
        $id = $stmt->fetchColumn();

        return $id === false ? null : (int) $id;
    }

    private function updateRow(int $shippingId, ShippingAddressEntity $address): void
    {
        $sql = 'UPDATE dtb_shipping SET '
            . 'name01 = :name01, '
            . 'name02 = :name02, '
            . 'postal_code = :postal_code, '
            . 'pref_id = :pref_id, '
            . 'addr01 = :addr01, '
            . 'addr02 = :addr02, '
            . 'phone_number = :phone_number, '
            . 'update_date = NOW() '
            . 'WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id' => $shippingId,
            ':name01' => $address->name01,
            ':name02' => $address->name02,
            ':postal_code' => $address->postalCode,
            ':pref_id' => $address->pref === 0 ? null : $address->pref,
            ':addr01' => $address->addr01,
            ':addr02' => $address->addr02,
            ':phone_number' => $address->phoneNumber,
        ]);
    }

    private function insertRow(int $orderId, ShippingAddressEntity $address): void
    {
        $sql = 'INSERT INTO dtb_shipping '
            . '(order_id, pref_id, name01, name02, postal_code, '
            . 'addr01, addr02, phone_number, '
            . 'create_date, update_date, discriminator_type) '
            . 'VALUES (:order_id, :pref_id, :name01, :name02, :postal_code, '
            . ':addr01, :addr02, :phone_number, NOW(), NOW(), :discriminator)';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':order_id' => $orderId,
            ':pref_id' => $address->pref === 0 ? null : $address->pref,
            ':name01' => $address->name01,
            ':name02' => $address->name02,
            ':postal_code' => $address->postalCode,
            ':addr01' => $address->addr01,
            ':addr02' => $address->addr02,
            ':phone_number' => $address->phoneNumber,
            ':discriminator' => self::DISCRIMINATOR,
        ]);
    }

    /**
     * @param array<string, mixed> $row dtb_shipping columns (the order_no
     *                                  is passed separately because the
     *                                  single-row read resolves it before
     *                                  the SELECT while listAll JOINs it).
     */
    private function hydrate(string $orderNo, array $row): ShippingAddressEntity
    {
        return new ShippingAddressEntity(
            orderNo: $orderNo,
            name01: (string) $row['name01'],
            name02: (string) $row['name02'],
            postalCode: $row['postal_code'] === null ? '' : (string) $row['postal_code'],
            pref: $row['pref_id'] === null ? 0 : (int) $row['pref_id'],
            addr01: $row['addr01'] === null ? '' : (string) $row['addr01'],
            addr02: $row['addr02'] === null ? '' : (string) $row['addr02'],
            phoneNumber: $row['phone_number'] === null ? '' : (string) $row['phone_number'],
        );
    }
}
