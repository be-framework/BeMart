<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use DateTimeImmutable;
use DateTimeZone;
use Exception;
use MyVendor\BeMart\Be\Reason\Entity\FinalizedOrderEntity;
use Override;
use PDO;

use function ctype_digit;

/**
 * Real PDO-backed Order write-side — Phase 2b.
 *
 * Mirrors {@see FakeOrderCommand} against the live EC-CUBE 4.3 schema
 * (`dtb_order`). The companion read side {@see SqlOrderQuery} already
 * exists (Phase 2a Step 3); this class writes the SAME column↔field
 * projection so a read-after-write round-trips exactly. Pure prepared
 * statements: no Doctrine, no ORM.
 *
 * Column mapping — identical to {@see SqlOrderQuery::hydrateFinalized}:
 *   order_no, pre_order_id, customer_id (cast int on write),
 *   payment_id ← paymentMethodId, subtotal, delivery_fee_total,
 *   charge, discount, tax, total, payment_total, add_point ← addPoint,
 *   use_point ← usePoint, order_status_id ← orderStatus,
 *   order_date, payment_date.
 *
 * Pre-order → finalized promotion (`register`)
 * --------------------------------------------
 * In EC-CUBE the pre-order row (orderStatus=PROCESSING(8)) is created
 * during the earlier doProceedToConfirm step; PurchaseFlow then mutates
 * the SAME row in place and OrderRepository commits it. The interface
 * docblock anticipated this: "Phase 2 will swap the fake for a
 * Ray.MediaQuery UPDATE against the existing pre-order row."
 *
 * So `register` is an UPSERT keyed by `pre_order_id`:
 *  - {@see \MyVendor\BeMart\Be\Final\CheckoutCompleted} — a pre-order
 *    row already exists (seeded by the confirm step / a fixture).
 *    `register` UPDATEs that row, promoting it from PROCESSING(8) to the
 *    entity's orderStatus (NEW(1) for a checkout) and stamping order_no,
 *    the totals and the dates. This preserves the contact columns
 *    (name01 / name02 / addr* etc.) the pre-order row already carries —
 *    {@see FinalizedOrderEntity} does not model them.
 *  - {@see \MyVendor\BeMart\Be\Final\AdminOrderCreated} — no prior
 *    pre-order (the Final fabricates `preOrderId === orderNo`). `register`
 *    INSERTs a fresh row. dtb_order.name01 / name02 are NOT NULL and not
 *    modelled by the entity, so the INSERT supplies a placeholder — an
 *    admin data-entry order has no shipping-contact capture in the
 *    Phase-2 slice (AdminOrderCreated's docblock defers it).
 *
 * `pre_order_id` carries a UNIQUE index, so the existence probe that
 * selects between UPDATE and INSERT is race-safe enough for the slice;
 * a concurrent confirm racing the same pre-order is an EC-CUBE-level
 * concern out of scope here.
 *
 * Order-item rows
 * ---------------
 * `OrderCommandInterface` deals only in {@see FinalizedOrderEntity},
 * which has NO items collection — every method here writes the
 * dtb_order header exclusively. dtb_order_item materialisation at
 * checkout time (out of the cart) is deferred exactly as
 * {@see SqlOrderQuery}'s docblock and {@see FakeFinalizedOrderStorage}
 * note; the order-item read path ({@see SqlOrderQuery::itemsByOrderNo})
 * stays fed by fixtures. No productCode→product_class_id JOIN is needed
 * here (unlike {@see SqlCartCommand}).
 *
 * Datetime round-trip
 * -------------------
 * {@see \MyVendor\BeMart\Be\Final\CheckoutCompleted} stamps order_date /
 * payment_date in ATOM format (`2026-05-20T12:00:00+09:00`);
 * {@see \MyVendor\BeMart\Be\Final\AdminOrderCreated} stamps `date('Y-m-d
 * H:i:s')`. `dtb_order.order_date` is a plain `datetime` and
 * {@see SqlOrderQuery} reads it back as a bare `Y-m-d H:i:s` string.
 * {@see normalizeDateTime} coerces every incoming value to
 * `Y-m-d H:i:s` in Asia/Tokyo so a read-after-write through
 * SqlOrderQuery yields the same string the rest of the order pipeline
 * already uses. An empty string (AdminOrderCreated passes `paymentDate
 * = ''`) becomes a real NULL — the column is nullable.
 *
 * order_status_id FK
 * ------------------
 * dtb_order.order_status_id has NO foreign-key constraint in the
 * EC-CUBE 4.3 schema (verified in
 * sql/schema/ec-cube-4.3-mysql-mysqldump.sql — the mtb_order_status
 * master is a lookup table, not an enforced parent). So unlike
 * customer_status / login_history_status, NO master-table seeding is
 * required: any in-range status int writes directly.
 *
 * DI is intentionally NOT wired in production (FakeOrderCommand remains
 * the bound implementation). The SQL impl is exercised via the test-only
 * override in AbstractResourceSqlTestCase.
 */
final class SqlOrderCommand implements OrderCommandInterface
{
    private const DISCRIMINATOR = 'order';

    /**
     * dtb_order.name01 / name02 are NOT NULL and not modelled by
     * {@see FinalizedOrderEntity}. When `register` has to INSERT a fresh
     * row (admin data-entry order, no prior pre-order to inherit contact
     * columns from) it supplies this placeholder. EC-CUBE's own admin
     * order-create form captures the buyer name; the Phase-2 BeMart
     * slice ({@see \MyVendor\BeMart\Be\Final\AdminOrderCreated}) defers
     * shipping-contact capture, so a sentinel keeps the NOT NULL columns
     * satisfiable without inventing request fields.
     */
    private const PLACEHOLDER_NAME = '-';

    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    #[Override]
    public function register(FinalizedOrderEntity $order): void
    {
        // Promotion vs. fresh insert is decided by whether a pre-order
        // row already exists. pre_order_id is UNIQUE so this resolves to
        // at most one row.
        if ($this->preOrderRowExists($order->preOrderId)) {
            $this->promotePreOrder($order);

            return;
        }

        $this->insertOrder($order);
    }

    #[Override]
    public function update(FinalizedOrderEntity $order): void
    {
        // In-place overwrite of an existing finalized row, keyed by
        // order_no. The caller ({@see \MyVendor\BeMart\Be\Final\AdminOrderUpdated})
        // has already merged editable fields onto the persisted shape and
        // preserved the non-editable ones, so every modelled column is
        // written verbatim. pre_order_id / customer_id are part of the
        // entity but are reused-from-current by the Final, so writing
        // them here is a harmless no-change. Contact columns (name01 …)
        // are untouched — not modelled, not in the SET list.
        $sql = 'UPDATE dtb_order SET '
            . 'customer_id = :customer_id, '
            . 'payment_id = :payment_id, '
            . 'subtotal = :subtotal, '
            . 'delivery_fee_total = :delivery_fee_total, '
            . 'charge = :charge, '
            . 'discount = :discount, '
            . 'tax = :tax, '
            . 'total = :total, '
            . 'payment_total = :payment_total, '
            . 'add_point = :add_point, '
            . 'use_point = :use_point, '
            . 'order_status_id = :order_status_id, '
            . 'order_date = :order_date, '
            . 'payment_date = :payment_date, '
            . 'update_date = NOW() '
            . 'WHERE order_no = :order_no';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':order_no' => $order->orderNo,
            ':customer_id' => $this->customerId($order->customerId),
            ':payment_id' => $this->paymentId($order->paymentMethodId),
            ':subtotal' => $order->subtotal,
            ':delivery_fee_total' => $order->deliveryFeeTotal,
            ':charge' => $order->charge,
            ':discount' => $order->discount,
            ':tax' => $order->tax,
            ':total' => $order->total,
            ':payment_total' => $order->paymentTotal,
            ':add_point' => $order->addPoint,
            ':use_point' => $order->usePoint,
            ':order_status_id' => $order->orderStatus,
            ':order_date' => $this->normalizeDateTime($order->orderDate),
            ':payment_date' => $this->normalizeDateTime($order->paymentDate),
        ]);
    }

    #[Override]
    public function updateStatus(string $orderNo, int $newStatus): void
    {
        // Single-column flip — the narrow surface is the whole point of
        // a dedicated mutator vs `update()` (state-machine semantics stay
        // observable, no full-entity rebuild for one column). A missing
        // row is a silent no-op: WHERE matches nothing, mirroring the
        // Fake's "concurrent-delete race we treat as a no-op" contract.
        $stmt = $this->pdo->prepare(
            'UPDATE dtb_order SET order_status_id = :status, '
            . 'update_date = NOW() WHERE order_no = :order_no',
        );
        $stmt->execute([
            ':status' => $newStatus,
            ':order_no' => $orderNo,
        ]);
    }

    /**
     * UNIQUE-indexed existence probe on pre_order_id. An empty
     * preOrderId never matches a real row (the column is nullable and a
     * fresh admin order carries no pre-order linkage) so the upsert
     * falls through to INSERT.
     */
    private function preOrderRowExists(string $preOrderId): bool
    {
        if ($preOrderId === '') {
            return false;
        }

        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM dtb_order WHERE pre_order_id = :pre_order_id LIMIT 1',
        );
        $stmt->execute([':pre_order_id' => $preOrderId]);

        return $stmt->fetchColumn() !== false;
    }

    /**
     * Promote the existing pre-order row (PROCESSING) to the entity's
     * orderStatus, stamping order_no / totals / dates. Contact columns
     * (name01 …) the pre-order row already holds are left intact.
     */
    private function promotePreOrder(FinalizedOrderEntity $order): void
    {
        $sql = 'UPDATE dtb_order SET '
            . 'order_no = :order_no, '
            . 'customer_id = :customer_id, '
            . 'payment_id = :payment_id, '
            . 'subtotal = :subtotal, '
            . 'delivery_fee_total = :delivery_fee_total, '
            . 'charge = :charge, '
            . 'discount = :discount, '
            . 'tax = :tax, '
            . 'total = :total, '
            . 'payment_total = :payment_total, '
            . 'add_point = :add_point, '
            . 'use_point = :use_point, '
            . 'order_status_id = :order_status_id, '
            . 'order_date = :order_date, '
            . 'payment_date = :payment_date, '
            . 'update_date = NOW() '
            . 'WHERE pre_order_id = :pre_order_id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':pre_order_id' => $order->preOrderId,
            ':order_no' => $order->orderNo,
            ':customer_id' => $this->customerId($order->customerId),
            ':payment_id' => $this->paymentId($order->paymentMethodId),
            ':subtotal' => $order->subtotal,
            ':delivery_fee_total' => $order->deliveryFeeTotal,
            ':charge' => $order->charge,
            ':discount' => $order->discount,
            ':tax' => $order->tax,
            ':total' => $order->total,
            ':payment_total' => $order->paymentTotal,
            ':add_point' => $order->addPoint,
            ':use_point' => $order->usePoint,
            ':order_status_id' => $order->orderStatus,
            ':order_date' => $this->normalizeDateTime($order->orderDate),
            ':payment_date' => $this->normalizeDateTime($order->paymentDate),
        ]);
    }

    /**
     * INSERT a fresh dtb_order row (no prior pre-order). Supplies the
     * NOT NULL name01 / name02 placeholders and the create_date /
     * update_date timestamps EC-CUBE's Timestampable would have written.
     */
    private function insertOrder(FinalizedOrderEntity $order): void
    {
        $sql = 'INSERT INTO dtb_order '
            . '(customer_id, payment_id, pre_order_id, order_no, '
            . 'name01, name02, subtotal, discount, delivery_fee_total, '
            . 'charge, tax, total, payment_total, add_point, use_point, '
            . 'order_status_id, order_date, payment_date, '
            . 'create_date, update_date, discriminator_type) '
            . 'VALUES (:customer_id, :payment_id, :pre_order_id, :order_no, '
            . ':name01, :name02, :subtotal, :discount, :delivery_fee_total, '
            . ':charge, :tax, :total, :payment_total, :add_point, :use_point, '
            . ':order_status_id, :order_date, :payment_date, '
            . 'NOW(), NOW(), :discriminator)';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':customer_id' => $this->customerId($order->customerId),
            ':payment_id' => $this->paymentId($order->paymentMethodId),
            ':pre_order_id' => $order->preOrderId === '' ? null : $order->preOrderId,
            ':order_no' => $order->orderNo,
            ':name01' => self::PLACEHOLDER_NAME,
            ':name02' => self::PLACEHOLDER_NAME,
            ':subtotal' => $order->subtotal,
            ':discount' => $order->discount,
            ':delivery_fee_total' => $order->deliveryFeeTotal,
            ':charge' => $order->charge,
            ':tax' => $order->tax,
            ':total' => $order->total,
            ':payment_total' => $order->paymentTotal,
            ':add_point' => $order->addPoint,
            ':use_point' => $order->usePoint,
            ':order_status_id' => $order->orderStatus,
            ':order_date' => $this->normalizeDateTime($order->orderDate),
            ':payment_date' => $this->normalizeDateTime($order->paymentDate),
            ':discriminator' => self::DISCRIMINATOR,
        ]);
    }

    /**
     * dtb_order.customer_id is `int unsigned` FK → dtb_customer.id;
     * {@see FinalizedOrderEntity}'s customerId is the BeMart-side string
     * handle. A numeric handle maps to the int; a non-numeric one
     * (FakeSession emits `customer-001`) cannot reference a real row, so
     * we write NULL — the column is nullable, the FK then tolerates the
     * absence. {@see SqlOrderQuery} casts customer_id back to a string
     * on read; NULL reads back as `''`, which is the honest projection
     * of "no resolvable customer".
     */
    private function customerId(string $customerId): int|null
    {
        return ctype_digit($customerId) ? (int) $customerId : null;
    }

    /**
     * dtb_order.payment_id is a nullable FK → dtb_payment. 0 is the
     * BeMart "no payment method" sentinel ({@see SqlOrderQuery} hydrates
     * a NULL payment_id back to 0); store it as a real NULL so the FK
     * holds when the dtb_payment master has no matching row.
     */
    private function paymentId(int $paymentMethodId): int|null
    {
        return $paymentMethodId > 0 ? $paymentMethodId : null;
    }

    /**
     * Coerce an incoming datetime string to `Y-m-d H:i:s` in Asia/Tokyo
     * so a read-after-write through {@see SqlOrderQuery} yields the same
     * bare-datetime string the rest of the order pipeline uses. Accepts
     * ATOM ({@see \MyVendor\BeMart\Be\Final\CheckoutCompleted}) and bare
     * `Y-m-d H:i:s` ({@see \MyVendor\BeMart\Be\Final\AdminOrderCreated})
     * alike. An empty string → NULL (the column is nullable; an admin
     * order with no payment captured carries `paymentDate = ''`).
     * An unparseable value also folds to NULL rather than raising — a
     * malformed timestamp is not worth aborting a committed order over.
     */
    private function normalizeDateTime(string $value): string|null
    {
        if ($value === '') {
            return null;
        }

        try {
            $dt = new DateTimeImmutable($value);
        } catch (Exception) {
            return null;
        }

        return $dt->setTimezone(new DateTimeZone('Asia/Tokyo'))
            ->format('Y-m-d H:i:s');
    }
}
