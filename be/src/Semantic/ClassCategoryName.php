<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\ClassCategoryNameFormatException;

use function mb_strlen;
use function trim;

/**
 * ClassCategory display name — EC-CUBE 4.3 dtb_class_category.name.
 * One concrete value along an axis (e.g. "Red", "S"). Non-empty,
 * <= 255 chars.
 */
final class ClassCategoryName
{
    #[Validate]
    public function validate(string|null $classCategoryName): void
    {
        if ($classCategoryName === null) {
            return;
        }

        if (trim($classCategoryName) === '') {
            throw new ClassCategoryNameFormatException();
        }

        if (mb_strlen($classCategoryName) > 255) {
            throw new ClassCategoryNameFormatException();
        }
    }
}
