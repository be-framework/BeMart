<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Entity;

/**
 * Catalog class name (規格名) — projection of EC-CUBE dtb_class_name
 * (Wave 7 catalog hierarchy slice).
 *
 * "ClassName" in EC-CUBE 4.x is the AXIS of a product variant — e.g.
 * "Color" or "Size" — NOT an OOP class. The concrete values along an
 * axis live in {@see ClassCategoryEntity} (e.g. "Red", "Blue" under
 * "Color"). A future ProductClass row joins one ClassName + one
 * ClassCategory pair onto a Product to form a SKU.
 */
final readonly class ClassNameEntity implements \Ray\MediaQuery\ToScalarInterface
{
    use MediaQueryJsonEntityTrait;

    public string $classNameId;

    public function __construct(
        int|string $classNameId,
        public string $name,
    ) {
        $this->classNameId = (string) $classNameId;
    }
}
