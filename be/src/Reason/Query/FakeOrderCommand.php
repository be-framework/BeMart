<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\FinalizedOrderEntity;
use Override;

final class FakeOrderCommand implements OrderCommandInterface
{
    public function __construct(
        private readonly FakeFinalizedOrderStorage $storage,
    ) {
    }

    #[Override]
    public function register(FinalizedOrderEntity $order): void
    {
        $this->storage->put($order);
    }

    /**
     * Wave 7 (doUpdateOrder): in-place overwrite of an existing row.
     * The Final has already merged editable fields onto the current row
     * and protected non-editable ones; the fake just round-trips through
     * the storage map (same keyspace as `register`, so the read path
     * picks the updated row up immediately).
     */
    #[Override]
    public function update(FinalizedOrderEntity $order): void
    {
        $this->storage->put($order);
    }

    /**
     * Wave 7 (doUpdateOrderStatus): single-field flip. Read-modify-write
     * through the same storage: if the row vanished between the Final's
     * load and this call (a concurrent-delete race), we treat the miss
     * as a no-op rather than fabricating a fresh row.
     */
    #[Override]
    public function updateStatus(string $orderNo, int $newStatus): void
    {
        $current = $this->storage->getByOrderNo($orderNo);
        if ($current === null) {
            return;
        }

        $this->storage->put(new FinalizedOrderEntity(
            orderNo: $current->orderNo,
            preOrderId: $current->preOrderId,
            customerId: $current->customerId,
            paymentMethodId: $current->paymentMethodId,
            subtotal: $current->subtotal,
            deliveryFeeTotal: $current->deliveryFeeTotal,
            charge: $current->charge,
            discount: $current->discount,
            tax: $current->tax,
            total: $current->total,
            paymentTotal: $current->paymentTotal,
            addPoint: $current->addPoint,
            usePoint: $current->usePoint,
            orderStatus: $newStatus,
            orderDate: $current->orderDate,
            paymentDate: $current->paymentDate,
        ));
    }
}
