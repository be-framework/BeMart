<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Entity;

/**
 * Catalog class category (規格分類) — projection of EC-CUBE
 * dtb_class_category (Wave 7 catalog hierarchy slice).
 *
 * "ClassCategory" in EC-CUBE 4.x is one concrete VALUE under a
 * {@see ClassNameEntity} axis — e.g. "Red" / "Blue" under "Color", or
 * "S" / "M" / "L" under "Size". NOT a taxonomy node (use
 * {@see CategoryEntity} for product taxonomy).
 *
 * The classNameId pins ownership to one axis; deleting an axis (or
 * deleting this row) is a flat-list operation here. Phase 2 will add
 * ProductClass referential-integrity guards once a real consumer
 * shows up.
 */
final readonly class ClassCategoryEntity implements \Ray\MediaQuery\ToScalarInterface
{
    use MediaQueryJsonEntityTrait;

    public string $classCategoryId;
    public string $classNameId;

    public function __construct(
        int|string $classCategoryId,
        int|string $classNameId,
        public string $name,
    ) {
        $this->classCategoryId = (string) $classCategoryId;
        $this->classNameId = (string) $classNameId;
    }
}
