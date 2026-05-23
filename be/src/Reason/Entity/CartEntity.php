<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Entity;

/**
 * Per-sale-type Cart aggregate (dtb_cart).
 *
 * cartKey = "{sessionPrefix}_{saleTypeId}" — EC-CUBE partitions one
 * shopping session into N carts, one per sale type (通常/予約/DL).
 * `items` are the existing CartItemEntity rows under this cartKey.
 */
final readonly class CartEntity implements \Ray\MediaQuery\ToScalarInterface
{
    use MediaQueryJsonEntityTrait;

    /** @param list<CartItemEntity> $items */
    public function __construct(
        public string $cartKey,
        public int $saleTypeId,
        public string $saleTypeName,
        public array $items,
        public int $totalPrice,
        public int $deliveryFeeTotal,
        public string $preOrderId,
    ) {
    }
}
