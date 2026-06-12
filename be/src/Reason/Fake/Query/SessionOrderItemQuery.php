<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Fake\Query;

use MyVendor\BeMart\Be\Reason\Entity\OrderItemEntity;
use MyVendor\BeMart\Be\Reason\Query\OrderItemQueryInterface;
use Override;

use function array_map;

/** Browser Fake order-item snapshots backed by SessionOrderStorage. */
final readonly class SessionOrderItemQuery implements OrderItemQueryInterface
{
    public function __construct(
        private SessionOrderStorage $orders,
    ) {
    }

    /** @return list<OrderItemEntity> */
    #[Override]
    public function listByOrderNo(string $orderNo): array
    {
        return array_map(
            static fn (array $item): OrderItemEntity => new OrderItemEntity(
                orderNo: $orderNo,
                productCode: (string) ($item['productCode'] ?? ''),
                productName: (string) ($item['productName'] ?? ''),
                quantity: (int) ($item['quantity'] ?? 0),
                unitPrice: (int) ($item['unitPrice'] ?? 0),
            ),
            $this->orders->itemRowsByOrderNo($orderNo),
        );
    }
}
