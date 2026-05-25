<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\AdminProductFetched;

/**
 * Input for goProduct (admin variant) — admin opens the full-detail
 * page for a single product.
 *
 *   GetAdminProductInput → AdminProductFetched  (Direct, safe read)
 *
 * Sibling of {@see GetProductInput} (Pilot 1, customer-side). ALPS
 * `goProduct` has one id whose tags cover both audiences; the two
 * Inputs differ in their AUTHZ ladder + the columns surfaced by their
 * Finals. See {@see AdminProductFetched} for the G-17 rationale.
 *
 * @link https://schema.org/Product
 */
#[Be(AdminProductFetched::class)]
final readonly class GetAdminProductInput
{
    /**
     * @psalm-taint-source input $productCode
     */
    public function __construct(
        public string $productCode,
    ) {
    }
}
