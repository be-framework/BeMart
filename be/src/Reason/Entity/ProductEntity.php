<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Entity;

/**
 * Flattened Product × ProductClass row, Pilot scope.
 *
 * Wave 8 extends the Pilot 1 shape with four admin-relevant fields
 * — `productStatus`, `description`, `searchWord`, `note` —
 * matching the EC-CUBE 4.3 dtb_product columns that the admin product
 * management transitions (Wave 8) exercise. Pilot 1's four fields
 * (`productCode`, `productName`, `price02`, `stock`) are preserved
 * verbatim with the same positional order, so all existing Pilot 1
 * tests round-trip unchanged.
 *
 * 厳密移植 alignment: `dtb_product` has NO `sort_no` column — product
 * ordering in EC-CUBE 4.3 lives on `dtb_product_category.sort_no`
 * (per-category). An earlier `sortNo` field on this entity was a
 * BeMart-only drift from the schema and has been dropped.
 *
 * The new fields default to safe values on construction to keep the
 * shape backward compatible: existing call sites that build a Pilot 1
 * shape can omit the trailing arguments. New admin-side call sites
 * (Wave 8 create / update / copy) always populate the full surface.
 *
 * The optional presentation fields at the tail (`imagePath`,
 * `categoryNames`, `tagNames`, `classNames`) are the HTML-screen
 * enrichment slice: they mirror EC-CUBE's `dtb_product_image`,
 * `dtb_product_category`, `dtb_product_tag` and class-category joins
 * without changing the existing create/update contract. Fake fixtures
 * can carry them; SQL reads them from the real EC-CUBE schema.
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
        public string|null $imagePath = null,
        /** @var list<string> */
        public array $categoryNames = [],
        /** @var list<string> */
        public array $tagNames = [],
        /** @var list<string> */
        public array $classNames = [],
    ) {
    }
}
