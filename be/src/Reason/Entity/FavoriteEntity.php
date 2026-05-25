<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Entity;

/**
 * Customer favorite — projection of EC-CUBE dtb_customer_favorite_product
 * (Pilot 13).
 *
 * Phase 3 enrichment — `fileName` carries the product's main-image file
 * name (the lowest-`sort_no` `dtb_product_image` row), so the favorites
 * list screen (EC-CUBE `Mypage/favorite.twig`) can render the product
 * thumbnail. It is the LAST, OPTIONAL constructor parameter: every
 * existing construction site (FavoriteAdded write, SqlFavoriteStorage /
 * FakeFavoriteStorage reads, the tests) passes its arguments by name, so
 * the trailing nullable field adds no positional ripple. `null` means
 * the product has no image — the screen falls back to the shared
 * `no_image_product` placeholder, mirroring CartItem's `mainImage`.
 *
 * The favorite WRITE path (`FavoriteAdded` → `FavoriteStorage::add`)
 * does not need the image — it is a display-only field re-derived on
 * read — so adds default to `null`.
 */
final readonly class FavoriteEntity
{
    public function __construct(
        public string $customerId,
        public string $productCode,
        public string $productName,
        public int $unitPrice,
        public string|null $fileName = null,
    ) {
    }
}
