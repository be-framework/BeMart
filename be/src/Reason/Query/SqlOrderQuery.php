<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\FinalizedOrderEntity;
use MyVendor\BeMart\Be\Reason\Entity\OrderEntity;
use MyVendor\BeMart\Be\Reason\Entity\OrderHistoryEntity;
use MyVendor\BeMart\Be\Reason\Entity\OrderHistoryItemEntity;
use MyVendor\BeMart\Be\Reason\Entity\OrderHistoryMailEntity;
use MyVendor\BeMart\Be\Reason\Entity\OrderHistoryShippingEntity;
use MyVendor\BeMart\Be\Reason\Entity\OrderItemEntity;
use Override;
use PDO;

/**
 * Real PDO-backed Order query — Phase 2a Step 3.
 *
 * Mirrors {@see FakeOrderQuery} against the live EC-CUBE 4.3 schema
 * (`dtb_order` for the header, `dtb_order_item` for line items). Pure
 * prepared statements — no Doctrine.
 *
 * The finalized-vs-pre-order split is encoded by `order_status_id`:
 * - `byPreOrderId`     reads PROCESSING(8) rows and returns
 *   {@see OrderEntity} (lean pre-order projection).
 * - `byOrderNo` / `listByCustomer` / `listAll` read finalized rows
 *   (`order_status_id <> 8`) and return {@see FinalizedOrderEntity}.
 * - `historyByOrderNo` reads a finalized row and JOINs `dtb_payment`
 *   (payment-method name), `dtb_shipping` (per-shipping address blocks),
 *   `dtb_order_item` (line items grouped by `shipping_id`) and
 *   `dtb_mail_history` (mail-delivery log) into the enriched
 *   {@see OrderHistoryEntity} screen projection behind goMypageHistory
 *   (Phase 3 enrichment).
 *
 * Mapping notes (see `sql/diff/entity-vs-eccube.md` §FinalizedOrderEntity
 * / §OrderEntity):
 * - `dtb_order.id` is `int unsigned`; BeMart `customerId` / `orderNo` /
 *   `preOrderId` are strings. We cast on read.
 * - Money columns are `decimal(12,2) unsigned`; we cast to int (yen,
 *   integer-only domain — fractional yen is a data error to be caught
 *   at the storage boundary by Phase 2b commands).
 * - `dtb_order_item.order_id` is the FK back to dtb_order; we join on it
 *   to resolve `order_no`. The order-item row freezes `product_name`
 *   and `price` at checkout time (catalog edits don't rewrite history).
 *
 * Pre-order items: not loaded here. `byPreOrderId` returns the lean
 * OrderEntity shape with an empty items list — the fake materialises
 * items from `var/fake/orders.json`, but in the SQL world cart items
 * live in `dtb_cart_item` and are out of scope for Step 3 (Cascade
 * Diamond callers that need items aren't using SQL yet).
 *
 * DI is intentionally NOT wired in Phase 2a; FakeOrderQuery remains
 * the bound implementation.
 */
final class SqlOrderQuery implements OrderQueryInterface
{
    /**
     * Excludes `order_status_id` and the wide shipping/contact columns
     * the diff report calls out as denormalised noise — we only project
     * the fields {@see FinalizedOrderEntity} models.
     */
    private const FINALIZED_COLUMNS = 'order_no, pre_order_id, customer_id, payment_id, '
        . 'subtotal, delivery_fee_total, charge, discount, tax, total, payment_total, '
        . 'add_point, use_point, order_status_id, order_date, payment_date';

    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    #[Override]
    public function byPreOrderId(string $preOrderId): OrderEntity|null
    {
        // Pre-orders are PROCESSING(8). Anything else is finalized and
        // therefore not a pre-order — return null so callers don't
        // accidentally re-process a completed order.
        $sql = 'SELECT pre_order_id, customer_id, payment_id, delivery_fee_total '
            . 'FROM dtb_order WHERE pre_order_id = :pre_order_id '
            . 'AND order_status_id = :status LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':pre_order_id' => $preOrderId,
            ':status' => FinalizedOrderEntity::STATUS_PROCESSING,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            return null;
        }

        // Pre-order items live in dtb_cart_item (per the diff report);
        // wiring that JOIN is deferred — the only Phase 2a consumer of
        // this method is the Fake, and SQL callers don't need items yet.
        return new OrderEntity(
            preOrderId: (string) $row['pre_order_id'],
            customerId: (string) $row['customer_id'],
            paymentMethodId: (int) $row['payment_id'],
            items: [],
            deliveryFeeTotal: (int) $row['delivery_fee_total'],
        );
    }

    #[Override]
    public function byOrderNo(string $orderNo): FinalizedOrderEntity|null
    {
        $sql = 'SELECT ' . self::FINALIZED_COLUMNS . ' FROM dtb_order '
            . 'WHERE order_no = :order_no AND order_status_id <> :processing LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':order_no' => $orderNo,
            ':processing' => FinalizedOrderEntity::STATUS_PROCESSING,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $this->hydrateFinalized($row);
    }

    #[Override]
    public function historyByOrderNo(string $orderNo): OrderHistoryEntity|null
    {
        // Header row + message + payment-method name. dtb_payment is a
        // LEFT JOIN: payment_id is a nullable FK, and the structure-only
        // dump leaves dtb_payment empty — a missing match must degrade to
        // the empty string, not drop the order. Pre-orders (PROCESSING)
        // are excluded — the history screen only shows finalized orders.
        $sql = 'SELECT o.id, o.order_no, o.customer_id, o.message, '
            . 'o.subtotal, o.delivery_fee_total, o.charge, o.discount, '
            . 'o.tax, o.total, o.payment_total, o.add_point, o.use_point, '
            . 'o.order_status_id, o.order_date, o.payment_date, '
            . 'p.payment_method '
            . 'FROM dtb_order o '
            . 'LEFT JOIN dtb_payment p ON p.id = o.payment_id '
            . 'WHERE o.order_no = :order_no '
            . 'AND o.order_status_id <> :processing LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':order_no' => $orderNo,
            ':processing' => FinalizedOrderEntity::STATUS_PROCESSING,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            return null;
        }

        $orderId = (int) $row['id'];

        return new OrderHistoryEntity(
            orderNo: (string) ($row['order_no'] ?? ''),
            customerId: (string) ($row['customer_id'] ?? ''),
            message: (string) ($row['message'] ?? ''),
            paymentMethod: (string) ($row['payment_method'] ?? ''),
            subtotal: (int) $row['subtotal'],
            deliveryFeeTotal: (int) $row['delivery_fee_total'],
            charge: (int) $row['charge'],
            discount: (int) $row['discount'],
            tax: (int) $row['tax'],
            total: (int) $row['total'],
            paymentTotal: (int) $row['payment_total'],
            addPoint: (int) $row['add_point'],
            usePoint: (int) $row['use_point'],
            orderStatus: (int) ($row['order_status_id'] ?? 0),
            orderDate: (string) ($row['order_date'] ?? ''),
            paymentDate: (string) ($row['payment_date'] ?? ''),
            shippings: $this->shippingsForOrder($orderId),
            mailHistories: $this->mailHistoriesForOrder($orderId),
        );
    }

    /**
     * Per-shipping address blocks for one finalized order, each carrying
     * its line items. dtb_shipping rows are ordered by `sort_no` then
     * `id` (EC-CUBE's display order); `mtb_pref` is LEFT JOINed for the
     * prefecture display name (empty table in the structure-only dump →
     * empty string). Items are grouped by `dtb_order_item.shipping_id`.
     *
     * @return list<OrderHistoryShippingEntity>
     */
    private function shippingsForOrder(int $orderId): array
    {
        $sql = 'SELECT s.id, s.name01, s.name02, s.kana01, s.kana02, '
            . 's.postal_code, s.addr01, s.addr02, s.phone_number, '
            . 's.delivery_name, s.delivery_date, s.delivery_time, '
            . 'pref.name AS pref_name '
            . 'FROM dtb_shipping s '
            . 'LEFT JOIN mtb_pref pref ON pref.id = s.pref_id '
            . 'WHERE s.order_id = :order_id '
            . 'ORDER BY s.sort_no IS NULL, s.sort_no ASC, s.id ASC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':order_id' => $orderId]);

        $out = [];
        while (($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
            $out[] = new OrderHistoryShippingEntity(
                name01: (string) ($row['name01'] ?? ''),
                name02: (string) ($row['name02'] ?? ''),
                kana01: (string) ($row['kana01'] ?? ''),
                kana02: (string) ($row['kana02'] ?? ''),
                postalCode: (string) ($row['postal_code'] ?? ''),
                prefName: (string) ($row['pref_name'] ?? ''),
                addr01: (string) ($row['addr01'] ?? ''),
                addr02: (string) ($row['addr02'] ?? ''),
                phoneNumber: (string) ($row['phone_number'] ?? ''),
                deliveryName: (string) ($row['delivery_name'] ?? ''),
                deliveryDate: (string) ($row['delivery_date'] ?? ''),
                deliveryTime: (string) ($row['delivery_time'] ?? ''),
                items: $this->itemsForShipping($orderId, (int) $row['id']),
            );
        }

        return $out;
    }

    /**
     * Line items for one shipping row of an order. dtb_order_item
     * references the shipping via `shipping_id`; rows whose `shipping_id`
     * is NULL (the simple-shipping fallback) are attached to the order's
     * first shipping by the caller via `:shipping_id IS NULL` matching.
     *
     * @return list<OrderHistoryItemEntity>
     */
    private function itemsForShipping(int $orderId, int $shippingId): array
    {
        $sql = 'SELECT oi.product_code, oi.product_name, oi.quantity, oi.price '
            . 'FROM dtb_order_item oi '
            . 'WHERE oi.order_id = :order_id '
            . 'AND (oi.shipping_id = :shipping_id OR oi.shipping_id IS NULL) '
            . 'ORDER BY oi.id ASC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':order_id' => $orderId,
            ':shipping_id' => $shippingId,
        ]);

        $out = [];
        while (($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
            $out[] = new OrderHistoryItemEntity(
                productCode: (string) ($row['product_code'] ?? ''),
                productName: (string) $row['product_name'],
                quantity: (int) $row['quantity'],
                unitPrice: (int) $row['price'],
            );
        }

        return $out;
    }

    /**
     * Mail-delivery history for one order, oldest send first (the order
     * EC-CUBE's history.twig walks the `Order.MailHistories` collection).
     *
     * @return list<OrderHistoryMailEntity>
     */
    private function mailHistoriesForOrder(int $orderId): array
    {
        $sql = 'SELECT mh.send_date, mh.mail_subject, mh.mail_body '
            . 'FROM dtb_mail_history mh '
            . 'WHERE mh.order_id = :order_id '
            . 'ORDER BY mh.send_date ASC, mh.id ASC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':order_id' => $orderId]);

        $out = [];
        while (($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
            $out[] = new OrderHistoryMailEntity(
                sendDate: (string) ($row['send_date'] ?? ''),
                mailSubject: (string) ($row['mail_subject'] ?? ''),
                mailBody: (string) ($row['mail_body'] ?? ''),
            );
        }

        return $out;
    }

    /** @return list<OrderItemEntity> */
    #[Override]
    public function itemsByOrderNo(string $orderNo): array
    {
        // dtb_order_item references dtb_order by id (not order_no), so
        // we JOIN to surface order_no on each row — keeps the caller
        // shape stable with FakeOrderQuery (no compound lookup).
        $sql = 'SELECT o.order_no, oi.product_code, oi.product_name, '
            . 'oi.quantity, oi.price '
            . 'FROM dtb_order_item oi '
            . 'INNER JOIN dtb_order o ON o.id = oi.order_id '
            . 'WHERE o.order_no = :order_no '
            . 'ORDER BY oi.id ASC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':order_no' => $orderNo]);

        $out = [];
        while (($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
            $out[] = new OrderItemEntity(
                orderNo: (string) $row['order_no'],
                productCode: (string) ($row['product_code'] ?? ''),
                productName: (string) $row['product_name'],
                quantity: (int) $row['quantity'],
                unitPrice: (int) $row['price'],
            );
        }

        return $out;
    }

    /** @return list<FinalizedOrderEntity> */
    #[Override]
    public function listByCustomer(string $customerId, int $limit = 10, int $offset = 0): array
    {
        // CustomerId is the BeMart-side string handle; dtb_order.customer_id
        // is int unsigned. Reject non-numeric ids early.
        if (! ctype_digit($customerId)) {
            return [];
        }

        // LIMIT / OFFSET inlined (safe — typed int) because emulated
        // prepares quote bound ints and MariaDB rejects them in LIMIT.
        $sql = 'SELECT ' . self::FINALIZED_COLUMNS . ' FROM dtb_order '
            . 'WHERE customer_id = :customer_id AND order_status_id <> :processing '
            . 'ORDER BY order_date DESC, id DESC '
            . 'LIMIT ' . $limit . ' OFFSET ' . $offset;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':customer_id' => (int) $customerId,
            ':processing' => FinalizedOrderEntity::STATUS_PROCESSING,
        ]);

        $out = [];
        while (($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
            $out[] = $this->hydrateFinalized($row);
        }

        return $out;
    }

    /** @return list<FinalizedOrderEntity> */
    #[Override]
    public function listAll(int $limit = 50, int $offset = 0): array
    {
        $sql = 'SELECT ' . self::FINALIZED_COLUMNS . ' FROM dtb_order '
            . 'WHERE order_status_id <> :processing '
            . 'ORDER BY order_date DESC, id DESC '
            . 'LIMIT ' . $limit . ' OFFSET ' . $offset;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':processing' => FinalizedOrderEntity::STATUS_PROCESSING]);

        $out = [];
        while (($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
            $out[] = $this->hydrateFinalized($row);
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrateFinalized(array $row): FinalizedOrderEntity
    {
        return new FinalizedOrderEntity(
            orderNo: (string) ($row['order_no'] ?? ''),
            preOrderId: (string) ($row['pre_order_id'] ?? ''),
            customerId: (string) ($row['customer_id'] ?? ''),
            paymentMethodId: (int) ($row['payment_id'] ?? 0),
            subtotal: (int) $row['subtotal'],
            deliveryFeeTotal: (int) $row['delivery_fee_total'],
            charge: (int) $row['charge'],
            discount: (int) $row['discount'],
            tax: (int) $row['tax'],
            total: (int) $row['total'],
            paymentTotal: (int) $row['payment_total'],
            addPoint: (int) $row['add_point'],
            usePoint: (int) $row['use_point'],
            orderStatus: (int) ($row['order_status_id'] ?? 0),
            orderDate: (string) ($row['order_date'] ?? ''),
            paymentDate: (string) ($row['payment_date'] ?? ''),
        );
    }
}
