<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\FinalizedOrderEntity;

/**
 * In-memory store for finalized Orders (orderStatus=NEW).
 *
 * Singleton-bound so OrderCommand writes and CheckoutCompletedTest reads
 * the same map. Phase 2 swaps for a Ray.MediaQuery UPDATE against
 * dtb_order that flips the row from PROCESSING(8) to NEW(1).
 */
final class FakeFinalizedOrderStorage
{
    /** @var array<string, FinalizedOrderEntity> */
    private array $orders = [];

    public function put(FinalizedOrderEntity $order): void
    {
        $this->orders[$order->orderNo] = $order;
    }

    public function getByOrderNo(string $orderNo): FinalizedOrderEntity|null
    {
        return $this->orders[$orderNo] ?? null;
    }

    public function getByPreOrderId(string $preOrderId): FinalizedOrderEntity|null
    {
        foreach ($this->orders as $order) {
            if ($order->preOrderId === $preOrderId) {
                return $order;
            }
        }

        return null;
    }
}
