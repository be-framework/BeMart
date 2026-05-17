<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\ProductFetched;

/**
 * Input for goProduct — fetch a product by its code.
 *
 * `$productCode` is validated by Semantic\ProductCode at Becoming time
 * (BeModule wires the validator by parameter name).
 *
 * @link https://schema.org/Product
 */
#[Be([ProductFetched::class])]
final readonly class GetProductInput
{
    public function __construct(
        public string $productCode,
    ) {
    }
}
