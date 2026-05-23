<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Entity;

/**
 * One line item inside an {@see OrderHistoryShippingEntity}.
 *
 * Phase 3 enrichment — the order-history detail screen (goMypageHistory)
 * renders item rows nested under each shipping block, mirroring EC-CUBE's
 * `Shipping.productOrderItems` loop in `Mypage/history.twig`.
 *
 * Carries the same four snapshot fields as {@see OrderItemEntity}
 * (productCode / productName / quantity / unitPrice) but is scoped to a
 * single shipping destination — `dtb_order_item.shipping_id` is the FK
 * that groups items per delivery target. A finalized order with one
 * shipping block has all items under that block; a multi-shipping order
 * fans them out.
 */
final readonly class OrderHistoryItemEntity implements \Ray\MediaQuery\ToScalarInterface
{
    use MediaQueryJsonEntityTrait;

    public function __construct(
        public string $productCode,
        public string $productName,
        public int $quantity,
        public int $unitPrice,
    ) {
    }
}
