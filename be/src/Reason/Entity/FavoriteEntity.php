<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Entity;

/**
 * Customer favorite — projection of EC-CUBE dtb_customer_favorite_product
 * (Pilot 13).
 */
final readonly class FavoriteEntity
{
    public function __construct(
        public string $customerId,
        public string $productCode,
        public string $productName,
        public int $unitPrice,
    ) {
    }
}
