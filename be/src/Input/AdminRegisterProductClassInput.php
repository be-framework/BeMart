<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\AdminProductClassRegistered;

/**
 * Input for doRegisterProductClass — admin registers one product 規格
 * (SKU) row for a product, carrying its sale price / stock / unlimited
 * flag / delivery fee (product-class-write).
 *
 *   AdminRegisterProductClassInput → AdminProductClassRegistered (Direct,
 *                                                       admin AUTHZ)
 */
#[Be(AdminProductClassRegistered::class)]
final readonly class AdminRegisterProductClassInput
{
    /**
     * @psalm-taint-source input $productCode
     * @psalm-taint-source input $price02
     */
    public function __construct(
        public string $productCode,
        public int $price02,
        public int $stock,
        public bool $stockUnlimited,
        public int $deliveryFee,
    ) {
    }
}
