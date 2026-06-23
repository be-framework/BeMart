<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Entity;

/**
 * Write-side ProductClass SKU row — projection of EC-CUBE
 * dtb_product_class for the admin 商品規格 登録 (register) operation.
 *
 * Distinct from {@see ProductClassEntity}, which is a READ projection
 * for doAddCartItem (carries no productClassId and joins sale-type
 * display fields). This entity carries the freshly-allocated
 * productClassId so the registered row is addressable.
 *
 * stock is null when stockUnlimited is true (mirroring EC-CUBE's
 * stock_unlimited semantics on dtb_product_class).
 */
final readonly class RegisteredProductClassEntity implements \Ray\MediaQuery\ToScalarInterface
{
    use MediaQueryJsonEntityTrait;

    public string $productClassId;

    public function __construct(
        int|string $productClassId,
        public string $productCode,
        public int $price02,
        public int|null $stock,
        public bool $stockUnlimited,
        public int $deliveryFee,
    ) {
        $this->productClassId = (string) $productClassId;
    }
}
