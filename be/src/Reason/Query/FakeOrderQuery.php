<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\CartItemEntity;
use MyVendor\BeMart\Be\Reason\Entity\FinalizedOrderEntity;
use MyVendor\BeMart\Be\Reason\Entity\OrderEntity;
use MyVendor\BeMart\Be\Reason\Entity\OrderItemEntity;
use Override;
use RuntimeException;

use function array_map;
use function dirname;
use function file_get_contents;
use function is_array;
use function json_decode;
use function sprintf;

use const JSON_THROW_ON_ERROR;

/**
 * Pilot 3 fake: reads var/fake/orders.json keyed by preOrderId.
 *
 * Phase 2 will swap this for a Ray.MediaQuery binding against dtb_order
 * (orderStatus=PROCESSING(8)) joined with dtb_order_item.
 *
 * The `itemsByOrderNo` path reads from FakeFinalizedOrderStorage rather
 * than the pre-order JSON fixture: finalized order items are written by
 * checkout (or seeded into the storage for past orders) and outlive the
 * pre-order row. Pilot 12 (doReorder) is the first consumer.
 */
final class FakeOrderQuery implements OrderQueryInterface
{
    /** @var array<string, OrderEntity>|null */
    private array|null $cache = null;

    public function __construct(
        private readonly FakeFinalizedOrderStorage $finalizedOrderStorage,
    ) {
    }

    #[Override]
    public function byPreOrderId(string $preOrderId): OrderEntity|null
    {
        return $this->load()[$preOrderId] ?? null;
    }

    /** @return list<OrderItemEntity> */
    #[Override]
    public function itemsByOrderNo(string $orderNo): array
    {
        return $this->finalizedOrderStorage->itemsByOrderNo($orderNo);
    }

    /** @return list<FinalizedOrderEntity> */
    #[Override]
    public function listByCustomer(string $customerId, int $limit = 10): array
    {
        return $this->finalizedOrderStorage->getByCustomerId($customerId, $limit);
    }

    /** @return array<string, OrderEntity> */
    private function load(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        $path = dirname(__DIR__, 3) . '/var/fake/orders.json';
        $json = file_get_contents($path);
        if ($json === false) {
            throw new RuntimeException(sprintf('Fake fixture missing: %s', $path));
        }

        /** @var array<string, array{preOrderId: string, customerId: string, paymentMethodId: int, items: list<array{productCode: string, quantity: int, price: int}>, deliveryFeeTotal: int}|string> $rows */
        $rows = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($rows)) {
            throw new RuntimeException(sprintf('Fake fixture must be a JSON object: %s', $path));
        }

        $entities = [];
        foreach ($rows as $key => $row) {
            if ($key === '$comment' || ! is_array($row)) {
                continue;
            }

            $entities[$row['preOrderId']] = new OrderEntity(
                preOrderId: $row['preOrderId'],
                customerId: $row['customerId'],
                paymentMethodId: $row['paymentMethodId'],
                items: array_map(
                    static fn (array $i): CartItemEntity => new CartItemEntity(
                        productCode: $i['productCode'],
                        quantity: $i['quantity'],
                        price: $i['price'],
                    ),
                    $row['items'],
                ),
                deliveryFeeTotal: $row['deliveryFeeTotal'],
            );
        }

        return $this->cache = $entities;
    }
}
