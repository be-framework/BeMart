<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\ClassCategoryCreated;

/**
 * Input for doCreateClassCategory — admin defines a new concrete
 * value under a product variant axis (Wave 7), e.g. "Red" under
 * "Color".
 *
 *   CreateClassCategoryInput → ClassCategoryCreated (Direct, admin
 *                                                    AUTHZ)
 */
#[Be(ClassCategoryCreated::class)]
final readonly class CreateClassCategoryInput
{
    /**
     * @psalm-taint-source input $classNameId
     * @psalm-taint-source input $classCategoryName
     */
    public function __construct(
        public string $classNameId,
        public string $classCategoryName,
    ) {
    }
}
