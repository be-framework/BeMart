<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Entity;

/**
 * Catalog category — projection of EC-CUBE dtb_category (Wave 7
 * catalog hierarchy slice).
 *
 * Category is a taxonomy tree node ("Food > Cookies > Chocolate"). The
 * parentId is the immediate-ancestor reference (null at the root). For
 * the first iteration we expose the table as a FLAT list of rows — the
 * UI flattens for display via sortNo. Nested-children projection is
 * deliberately out of scope; Phase 2 can add a hierarchical builder
 * once a real consumer asks for it.
 */
final readonly class CategoryEntity
{
    public function __construct(
        public string $categoryId,
        public string $categoryName,
        public string|null $parentId,
        public int $sortNo,
    ) {
    }
}
