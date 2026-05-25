<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\AdminProductCopied;

/**
 * Input for doCopyProduct — admin clones a product under a new code.
 *
 *   AdminCopyProductInput → AdminProductCopied  (Direct, unsafe)
 *
 * ALPS `doCopyProduct.type=unsafe`. Two product codes on the wire:
 * the source selector (`productCode`) and the target slot
 * (`newProductCode`). Both are validated by the matching Semantic
 * classes ({@see \MyVendor\BeMart\Be\Semantic\ProductCode} and
 * {@see \MyVendor\BeMart\Be\Semantic\NewProductCode}, which share
 * the same format rules) and an explicit ProductNotFound / 409
 * ladder lives in the Final.
 *
 * @link https://schema.org/Product
 */
#[Be(AdminProductCopied::class)]
final readonly class AdminCopyProductInput
{
    /**
     * @psalm-taint-source input $productCode
     * @psalm-taint-source input $newProductCode
     */
    public function __construct(
        public string $productCode,
        public string $newProductCode,
    ) {
    }
}
