<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\CategoryNameFormatException;

use function mb_strlen;
use function trim;

/**
 * Category display name — EC-CUBE 4.3 dtb_category.name.
 *
 * Non-empty, <= 255 chars. Same shape as {@see ProductName}; treated as
 * trimmable so a name of only whitespace fails.
 */
final class CategoryName
{
    #[Validate]
    public function validate(string|null $categoryName): void
    {
        if ($categoryName === null) {
            return;
        }

        if (trim($categoryName) === '') {
            throw new CategoryNameFormatException();
        }

        if (mb_strlen($categoryName) > 255) {
            throw new CategoryNameFormatException();
        }
    }
}
