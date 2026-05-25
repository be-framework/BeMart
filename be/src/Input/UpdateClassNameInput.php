<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\ClassNameUpdated;

/**
 * Input for doUpdateClassName — admin renames a product variant axis
 * (Wave 7).
 *
 *   UpdateClassNameInput → ClassNameUpdated (Direct, idempotent)
 */
#[Be(ClassNameUpdated::class)]
final readonly class UpdateClassNameInput
{
    /**
     * @psalm-taint-source input $classNameId
     * @psalm-taint-source input $classNameLabel
     */
    public function __construct(
        public string $classNameId,
        public string|null $classNameLabel = null,
    ) {
    }
}
