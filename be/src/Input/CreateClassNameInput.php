<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\ClassNameCreated;

/**
 * Input for doCreateClassName — admin defines a new product variant
 * axis (Wave 7), e.g. "Color" or "Size".
 *
 *   CreateClassNameInput → ClassNameCreated (Direct, admin AUTHZ)
 *
 * `classNameId` is generated server-side; the body only carries the
 * label.
 */
#[Be(ClassNameCreated::class)]
final readonly class CreateClassNameInput
{
    /**
     * @psalm-taint-source input $classNameLabel
     */
    public function __construct(
        public string $classNameLabel,
    ) {
    }
}
