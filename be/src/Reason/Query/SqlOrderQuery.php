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

use function ctype_digit;

final class SqlOrderQuery implements OrderQueryInterface
{
    public function __construct(private readonly MediaQueryExecutor $db) {}

    #[Override]
    public function byPreOrderId(string $preOrderId): OrderEntity|null
    {
        $row = $this->db->row('order_by_pre_order_id', [
            'preOrderId' => $preOrderId,
            'status' => FinalizedOrderEntity::STATUS_PROCESSING,
        ]);

        return $row === null ? null : new OrderEntity(
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
        $row = $this->db->row('order_by_order_no', [
            'orderNo' => $orderNo,
            'processing' => FinalizedOrderEntity::STATUS_PROCESSING,
        ]);

        return $row === null ? null : $this->hydrateFinalized($row);
    }

    #[Override]
    public function historyByOrderNo(string $orderNo): OrderHistoryEntity|null
    {
        $row = $this->db->row('order_history_header', [
            'orderNo' => $orderNo,
            'processing' => FinalizedOrderEntity::STATUS_PROCESSING,
        ]);
        if ($row === null) {
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

    /** @return list<OrderItemEntity> */
    #[Override]
    public function itemsByOrderNo(string $orderNo): array
    {
        return array_map(
            static fn (array $row): OrderItemEntity => new OrderItemEntity(
                orderNo: (string) $row['order_no'],
                productCode: (string) ($row['product_code'] ?? ''),
                productName: (string) $row['product_name'],
                quantity: (int) $row['quantity'],
                unitPrice: (int) $row['price'],
            ),
            $this->db->rows('order_items_by_order_no', ['orderNo' => $orderNo]),
        );
    }

    /** @return list<FinalizedOrderEntity> */
    #[Override]
    public function listByCustomer(string $customerId, int $limit = 10, int $offset = 0): array
    {
        if (! ctype_digit($customerId)) {
            return [];
        }

        return array_map($this->hydrateFinalized(...), $this->db->rows('order_list_by_customer', [
            'customerId' => (int) $customerId,
            'processing' => FinalizedOrderEntity::STATUS_PROCESSING,
            'limit' => $limit,
            'offset' => $offset,
        ]));
    }

    /** @return list<FinalizedOrderEntity> */
    #[Override]
    public function listAll(int $limit = 50, int $offset = 0): array
    {
        return array_map($this->hydrateFinalized(...), $this->db->rows('order_list_all', [
            'processing' => FinalizedOrderEntity::STATUS_PROCESSING,
            'limit' => $limit,
            'offset' => $offset,
        ]));
    }

    /** @return list<OrderHistoryShippingEntity> */
    private function shippingsForOrder(int $orderId): array
    {
        $out = [];
        foreach ($this->db->rows('order_history_shippings', ['orderId' => $orderId]) as $row) {
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

    /** @return list<OrderHistoryItemEntity> */
    private function itemsForShipping(int $orderId, int $shippingId): array
    {
        return array_map(
            static fn (array $row): OrderHistoryItemEntity => new OrderHistoryItemEntity(
                productCode: (string) ($row['product_code'] ?? ''),
                productName: (string) $row['product_name'],
                quantity: (int) $row['quantity'],
                unitPrice: (int) $row['price'],
            ),
            $this->db->rows('order_history_items', ['orderId' => $orderId, 'shippingId' => $shippingId]),
        );
    }

    /** @return list<OrderHistoryMailEntity> */
    private function mailHistoriesForOrder(int $orderId): array
    {
        return array_map(
            static fn (array $row): OrderHistoryMailEntity => new OrderHistoryMailEntity(
                sendDate: (string) ($row['send_date'] ?? ''),
                mailSubject: (string) ($row['mail_subject'] ?? ''),
                mailBody: (string) ($row['mail_body'] ?? ''),
            ),
            $this->db->rows('order_history_mails', ['orderId' => $orderId]),
        );
    }

    /** @param array<string, mixed> $row */
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
