<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;

/**
 * Stock-unlimited flag — EC-CUBE 4.3 dtb_product_class.stock_unlimited.
 *
 * true ⇒ stock is null and the product is treated as having no
 * upper cap. Type-only assertion: the bool type itself is the contract.
 */
final class StockUnlimited
{
    #[Validate]
    public function validate(bool $stockUnlimited): void
    {
        // Type assertion only — bool itself is the contract.
    }
}
