<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\ClassNameLabelFormatException;

use function mb_strlen;
use function trim;

/**
 * ClassName label — EC-CUBE 4.3 dtb_class_name.name. The visible axis
 * name (e.g. "Color", "Size"). Non-empty, <= 255 chars.
 */
final class ClassNameLabel
{
    #[Validate]
    public function validate(string|null $classNameLabel): void
    {
        if ($classNameLabel === null) {
            return;
        }

        if (trim($classNameLabel) === '') {
            throw new ClassNameLabelFormatException();
        }

        if (mb_strlen($classNameLabel) > 255) {
            throw new ClassNameLabelFormatException();
        }
    }
}
