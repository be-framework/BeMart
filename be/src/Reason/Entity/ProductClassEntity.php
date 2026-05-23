<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Entity;

/**
 * Server-fetched ProductClass row needed by doAddCartItem.
 *
 * Fields are server-only (no client input). Phase 2 observation lives
 * in var/fake/product_classes.json; Phase 2 swap will back this with
 * Ray.MediaQuery against dtb_product_class + join dtb_sale_type.
 */
final readonly class ProductClassEntity implements \Ray\MediaQuery\ToScalarInterface
{
    use MediaQueryJsonEntityTrait;

    public function __construct(
        public string $productCode,
        public string $productName,
        public int|null $stock,
        public bool $stockUnlimited,
        public int|null $saleLimit,
        public int $price02,
        public int $deliveryFee,
        public string $saleTypeName,
        public int $saleTypeId,
    ) {
    }
}
