<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\StorefrontProductListFetched;

/**
 * Input for goProductList (storefront) — the customer-facing catalog
 * listing.
 *
 *   GetStorefrontProductListInput → StorefrontProductListFetched
 *   (Direct, safe read)
 *
 * Anonymous-accessible — no AUTHN/AUTHZ. This is the customer-facing
 * sibling of {@see GetProductListInput} (the admin grid): where the
 * admin Final walks every productStatus and gates on an admin session,
 * this storefront variant has no firewall and the Final projects only
 * STATUS_VISIBLE rows (公開 products — hidden / withdrawn are never
 * exposed to the storefront).
 *
 * Parameterless first iteration — the storefront list shows every
 * visible product. Phase 2 will add category / keyword / tag filters
 * and pagination per EC-CUBE's `Product/list.twig` search form.
 *
 * @link https://schema.org/SearchAction
 */
#[Be(StorefrontProductListFetched::class)]
final readonly class GetStorefrontProductListInput
{
    public function __construct()
    {
    }
}
