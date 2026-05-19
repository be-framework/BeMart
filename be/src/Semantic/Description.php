<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\DescriptionFormatException;

use function mb_strlen;

/**
 * Product description (商品説明) — covers EC-CUBE 4.3
 * dtb_product.description_list and description_detail. Wave 8
 * (doCreateProduct / doUpdateProduct).
 *
 * Length-bounded to keep oversized payloads from reaching the
 * admin form storage layer. EC-CUBE's column is TEXT (effectively
 * unbounded); the 4000-char cap here is a safety rail rather than
 * a business rule.
 */
final class Description
{
    #[Validate]
    public function validate(string|null $description): void
    {
        if ($description === null) {
            return;
        }

        if (mb_strlen($description) > 4000) {
            throw new DescriptionFormatException();
        }
    }
}
