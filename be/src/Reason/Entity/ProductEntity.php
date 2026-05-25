<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Entity;

/**
 * Flattened Product × ProductClass row, Pilot scope.
 *
 * Wave 8 extends the Pilot 1 shape with five admin-relevant fields
 * — `productStatus`, `description`, `searchWord`, `note`, `sortNo` —
 * matching the EC-CUBE 4.3 dtb_product columns that the admin product
 * management transitions (Wave 8) exercise. Pilot 1's four fields
 * (`productCode`, `productName`, `price02`, `stock`) are preserved
 * verbatim with the same positional order, so all existing Pilot 1
 * tests round-trip unchanged.
 *
 * The new fields default to safe values on construction to keep the
 * shape backward compatible: existing call sites that build a Pilot 1
 * shape can omit the trailing arguments. New admin-side call sites
 * (Wave 8 create / update / copy) always populate the full surface.
 *
 * Mirrors what get_product.sql would return in Phase 2 once
 * Ray.MediaQuery + a real DSN replace FakeProductQuery: a Product
 * joined with one representative ProductClass.
 *
 * Status enum (matches `productStatus` ALPS descriptor):
 *   1 = 公開 (Visible — front + admin)
 *   2 = 非公開 (Hidden — admin only)
 *   3 = 廃止 (Withdrawn — soft delete, default-filtered from admin too)
 */
final readonly class ProductEntity
{
    /** Visible to front + admin. */
    public const int STATUS_VISIBLE = 1;

    /** Hidden from front, admin only. */
    public const int STATUS_HIDDEN = 2;

    /** Soft-deleted, default-filtered. */
    public const int STATUS_WITHDRAWN = 3;

    public function __construct(
        public string $productCode,
        public string $productName,
        public int $price02,
        public int|null $stock,
        public int $productStatus = self::STATUS_VISIBLE,
        public string|null $description = null,
        public string|null $searchWord = null,
        public string|null $note = null,
        public int|null $sortNo = null,
    ) {
    }
}
