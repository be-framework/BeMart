<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\PaymentMethodAdminEntity;
use Override;
use PDO;

use function ctype_digit;

/**
 * Real PDO-backed PaymentMethodAdmin storage — Phase 2b.
 *
 * Mirrors {@see FakePaymentMethodAdminStorage} against the live
 * EC-CUBE 4.3 schema (`dtb_payment`). The 6-field
 * PaymentMethodAdminEntity projection (paymentId / paymentMethodName /
 * charge / ruleMin / ruleMax / visible) lines up with EC-CUBE columns
 * id / payment_method / charge / rule_min / rule_max / visible, with
 * the coercions documented below.
 *
 * Scope (Wave 9θ — same as PaymentMethodAdminEntity):
 *   The 6-field projection above. dtb_payment has seven more columns
 *   (creator_id, sort_no, fixed, payment_image, method_class,
 *   create_date, update_date) and a sibling join table
 *   `dtb_payment_option` (links payments to deliveries). None are part
 *   of PaymentMethodAdminStorageInterface — the admin master CRUD UX
 *   only edits the 6 projected fields. On INSERT:
 *     - creator_id is always written NULL (FK → dtb_member, empty in
 *       the structure-only dump so a non-NULL value would raise FK
 *       1452 — same precedent as SqlNewsStorage / SqlBlockStorage).
 *     - sort_no is written NULL (column-nullable, no ordering UX in
 *       this slice).
 *     - fixed is written 1 (NOT NULL DEFAULT 1 — EC-CUBE's flag for a
 *       non-removable system payment; we write the schema default).
 *     - payment_image / method_class are written NULL (no UI for them
 *       in the BeMart admin slice; both column-nullable).
 *     - create_date / update_date are maintained with NOW() (same
 *       Doctrine Timestampable behavior SqlBlockStorage mimics — no
 *       timezone column on dtb_payment, datetime is server-local).
 *     - discriminator_type is 'payment' (the value EC-CUBE writes —
 *       Doctrine single-table inheritance discriminator).
 *   On DELETE we issue a defensive
 *   `DELETE FROM dtb_payment_option WHERE payment_id = ?` first so the
 *   FK (FK_5631540D4C3A3BB, payment_option.payment_id → payment.id)
 *   does not block the row deletion if a row was seeded externally —
 *   same shape SqlBlockStorage uses against dtb_block_position. The
 *   Wave 9θ admin slice never INSERTs a payment_option row, but an
 *   externally-seeded one would otherwise raise FK 1451. Idempotent —
 *   zero rows is fine.
 *
 * Coercions:
 *   - `id` is `int unsigned`, PaymentMethodAdminEntity::paymentId is
 *     `string` → cast `(string) (int)` on read, parse with
 *     `ctype_digit` on write. A non-numeric incoming paymentId (e.g.
 *     32-char hex from {@see FakePaymentMethodAdminIdGenerator}) is
 *     rejected: getById returns null, put no-ops, remove no-ops. Keeps
 *     {@see \MyVendor\BeMart\Be\Final\PaymentMethodAdminUpdated} /
 *     {@see \MyVendor\BeMart\Be\Final\PaymentMethodAdminDeleted} on
 *     their normal 404 path instead of raising a PDO exception — same
 *     shape the Fake exhibits when the id is absent. Same convention as
 *     {@see SqlBlockStorage} / {@see SqlTagStorage} / {@see SqlNewsStorage}.
 *   - `payment_method` is nullable in EC-CUBE but non-null on the
 *     Entity → hydrator coalesces NULL → '' so the projection's
 *     non-null shape is preserved across externally-inserted rows.
 *   - `charge` is `decimal(12,2) unsigned` nullable (DEFAULT 0.00),
 *     Entity::charge is `int` → cast `(int)` on read (a NULL coalesces
 *     to 0), `(int)` on write. JPY money has no fractional part — the
 *     decimal scale is an EC-CUBE artefact.
 *   - `rule_min` / `rule_max` are `decimal(12,2) unsigned` nullable,
 *     Entity::ruleMin / ruleMax are `int|null` → NULL stays NULL, a
 *     present value casts `(int)`.
 *   - `visible` is `tinyint(1) NOT NULL DEFAULT 1`, Entity::visible is
 *     `bool` → cast `(bool) (int)` on read, `(int)` on write.
 *
 * Upsert convention (`put`):
 *   paymentId is pre-allocated by {@see \MyVendor\BeMart\Be\Reason\Service\SqlPaymentMethodAdminIdGenerator}
 *   before `put` is called (PaymentMethodAdminCreated assigns
 *   `$entity->paymentId` from the generator output, so the storage
 *   receives an id-bearing entity). `put` probes
 *   `SELECT 1 WHERE id = ?`; hit → UPDATE, miss → INSERT with the
 *   explicit id. ALPS defines `doUpdatePayment` (PaymentMethodAdminUpdated
 *   merges + put on the same id) so the UPDATE branch is actively
 *   exercised, same as the Block / News / Page flows.
 *
 * List ordering: `ORDER BY id ASC` — the contract test does not assert
 * order, only count and presence. Same shape parity convention as
 * SqlBlockStorage / SqlTagStorage / SqlNewsStorage.
 *
 * DI is intentionally NOT wired in production (FakePaymentMethodAdminStorage
 * remains the bound implementation). The SQL impl is exercised via the
 * test-only override in AbstractResourceSqlTestCase.
 */
final class SqlPaymentMethodAdminStorage implements PaymentMethodAdminStorageInterface
{
    private const SELECT_COLUMNS = 'id, payment_method, charge, rule_min, rule_max, visible';

    private const DISCRIMINATOR = 'payment';

    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    /** @return list<PaymentMethodAdminEntity> */
    #[Override]
    public function list(): array
    {
        $sql = 'SELECT ' . self::SELECT_COLUMNS . ' FROM dtb_payment '
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
    public function getById(string $paymentId): PaymentMethodAdminEntity|null
    {
        if (! ctype_digit($paymentId)) {
            // Non-numeric ids (e.g. 32-char hex from
            // FakePaymentMethodAdminIdGenerator) can never match an int
            // PK. Surface as miss so PaymentMethodAdminUpdated /
            // PaymentMethodAdminDeleted raise their normal 404 instead
            // of throwing a PDO error.
            return null;
        }

        $sql = 'SELECT ' . self::SELECT_COLUMNS . ' FROM dtb_payment '
            . 'WHERE id = :id LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => (int) $paymentId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $this->hydrate($row);
    }

    #[Override]
    public function put(PaymentMethodAdminEntity $payment): void
    {
        if (! ctype_digit($payment->paymentId)) {
            // Defensive: a non-numeric id we cannot persist. The Fake
            // generator emits 32-char hex; production must rebind to
            // SqlPaymentMethodAdminIdGenerator before swapping in this
            // storage.
            return;
        }

        $id = (int) $payment->paymentId;

        $existsStmt = $this->pdo->prepare(
            'SELECT 1 FROM dtb_payment WHERE id = :id LIMIT 1',
        );
        $existsStmt->execute([':id' => $id]);
        $exists = $existsStmt->fetchColumn() !== false;

        if ($exists) {
            $sql = 'UPDATE dtb_payment SET '
                . 'payment_method = :payment_method, '
                . 'charge = :charge, '
                . 'rule_min = :rule_min, '
                . 'rule_max = :rule_max, '
                . 'visible = :visible, '
                . 'update_date = NOW() '
                . 'WHERE id = :id';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':id' => $id,
                ':payment_method' => $payment->paymentMethodName,
                ':charge' => $payment->charge,
                ':rule_min' => $payment->ruleMin,
                ':rule_max' => $payment->ruleMax,
                ':visible' => (int) $payment->visible,
            ]);

            return;
        }

        // INSERT with explicit id. creator_id is NULL (dtb_member is
        // empty in the structure-only dump so any non-NULL value would
        // raise FK 1452). sort_no / payment_image / method_class are
        // NULL (column-nullable, no UI in the BeMart admin slice).
        // fixed is 1 (NOT NULL DEFAULT 1 — schema default).
        // discriminator_type is 'payment'.
        $sql = 'INSERT INTO dtb_payment '
            . '(id, creator_id, payment_method, charge, rule_max, sort_no, '
            . 'fixed, payment_image, rule_min, method_class, visible, '
            . 'create_date, update_date, discriminator_type) '
            . 'VALUES (:id, NULL, :payment_method, :charge, :rule_max, NULL, '
            . '1, NULL, :rule_min, NULL, :visible, '
            . 'NOW(), NOW(), :discriminator)';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':payment_method' => $payment->paymentMethodName,
            ':charge' => $payment->charge,
            ':rule_max' => $payment->ruleMax,
            ':rule_min' => $payment->ruleMin,
            ':visible' => (int) $payment->visible,
            ':discriminator' => self::DISCRIMINATOR,
        ]);
    }

    #[Override]
    public function remove(string $paymentId): void
    {
        if (! ctype_digit($paymentId)) {
            // Silent no-op on a non-numeric id — same shape as the Fake
            // which `unset()`s a missing key without raising.
            return;
        }

        $id = (int) $paymentId;

        // Drop any dtb_payment_option links for this payment first so
        // FK_5631540D4C3A3BB (payment_option.payment_id → payment.id)
        // does not block the row deletion. The Wave 9θ admin slice
        // never INSERTs an option row, but an externally-seeded one
        // would otherwise raise FK 1451. Idempotent — zero rows is fine.
        $stmt = $this->pdo->prepare(
            'DELETE FROM dtb_payment_option WHERE payment_id = :id',
        );
        $stmt->execute([':id' => $id]);

        $stmt = $this->pdo->prepare('DELETE FROM dtb_payment WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(array $row): PaymentMethodAdminEntity
    {
        return new PaymentMethodAdminEntity(
            paymentId: (string) (int) $row['id'],
            // payment_method is nullable in EC-CUBE but non-null on the
            // Entity — coalesce NULL → '' so the projection shape stays
            // stable across externally-inserted rows.
            paymentMethodName: $row['payment_method'] === null
                ? ''
                : (string) $row['payment_method'],
            // charge is decimal(12,2) nullable; JPY money has no
            // fractional part — coalesce NULL → 0, truncate to int.
            charge: (int) ($row['charge'] ?? 0),
            ruleMin: $row['rule_min'] === null ? null : (int) $row['rule_min'],
            ruleMax: $row['rule_max'] === null ? null : (int) $row['rule_max'],
            visible: (bool) (int) $row['visible'],
        );
    }
}
