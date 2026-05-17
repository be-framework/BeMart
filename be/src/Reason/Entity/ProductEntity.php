<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Entity;

/**
 * Flattened Product × ProductClass row, Pilot scope.
 *
 * Mirrors what get_product.sql would return in Phase 2 once Ray.MediaQuery
 * + a real DSN replace FakeProductQuery: a Product joined with one
 * representative ProductClass (no class options, no images, no categories).
 */
final readonly class ProductEntity
{
    public function __construct(
        public string $productCode,
        public string $productName,
        public int $price02,
        public int|null $stock,
    ) {
    }
}
