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

    /**
     * @param list<CartItemEntity> $items
     * @param array{
     *   name01?: string, name02?: string, kana01?: string|null, kana02?: string|null,
     *   companyName?: string|null, email?: string, phoneNumber?: string|null,
     *   postalCode?: string|null, pref?: int|null, addr01?: string|null, addr02?: string|null
     * } $customerSnapshot
     */
    public function __construct(
        public string $preOrderId,
        public string $customerId,
        public int $paymentMethodId,
        public array $items,
        public int $deliveryFeeTotal,
        public array $customerSnapshot = [],
    ) {
    }
}
