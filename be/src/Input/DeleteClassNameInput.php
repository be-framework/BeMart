<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\ClassNameDeleted;

/**
 * Input for doDeleteClassName — admin removes a product variant axis
 * (Wave 7).
 *
 *   DeleteClassNameInput → ClassNameDeleted (Direct, idempotent)
 */
#[Be(ClassNameDeleted::class)]
final readonly class DeleteClassNameInput
{
    /**
     * @psalm-taint-source input $classNameId
     */
    public function __construct(
        public string $classNameId,
    ) {
    }
}
