<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Entity;

/**
 * Pre-order Order aggregate (dtb_order, orderStatus=PROCESSING(8)).
 *
 * EC-CUBE creates this row when the customer enters the Shopping page;
 * confirm() / checkout() operate on it. Pilot 3 models only the fields the
 * Cascade Diamond actually reads (preOrderId, paymentMethodId, customerId)
 * plus the source items needed to recompute totals.
 */
final readonly class OrderEntity implements \Ray\MediaQuery\ToScalarInterface
{
    use MediaQueryJsonEntityTrait;

    /** @param list<CartItemEntity> $items */
    public function __construct(
        public string $preOrderId,
        public string $customerId,
        public int $paymentMethodId,
        public array $items,
        public int $deliveryFeeTotal,
    ) {
    }
}
