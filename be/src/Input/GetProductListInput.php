<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\ProductListFetched;

/**
 * Input for goProductList (admin) — admin grid with name filter +
 * pagination.
 *
 *   GetProductListInput → ProductListFetched  (Direct, safe read)
 *
 * Admin-only endpoint. AUTHN/AUTHZ live in the Final via Wave 4
 * AdminSessionInterface (null admin session → 403). The customer-
 * facing product list (when it lands) will be a sibling Input.
 *
 * Filter scope (Wave 8 first iteration):
 *   - nameKeyword — substring on productName
 *   - limit + offset — pagination
 *
 * Phase 2 will add category / tag / stockState / saleType filters per
 * the EC-CUBE admin form.
 *
 * @link https://schema.org/SearchAction
 */
#[Be(ProductListFetched::class)]
final readonly class GetProductListInput
{
    /**
     * @psalm-taint-source input $nameKeyword
     * @psalm-taint-source input $limit
     * @psalm-taint-source input $offset
     */
    public function __construct(
        public string|null $nameKeyword = null,
        public int $limit = 50,
        public int $offset = 0,
    ) {
    }
}
