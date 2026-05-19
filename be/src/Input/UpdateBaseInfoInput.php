<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\BaseInfoUpdated;

/**
 * Input for doUpdateBaseInfo — admin updates the shop base info.
 *
 *   UpdateBaseInfoInput → BaseInfoUpdated (Final — Direct, idempotent)
 *
 * dtb_base_info is a single-row table; the request replaces the row
 * wholesale (no per-field PATCH). Only the shopName is required —
 * an empty shop name would break the storefront header / email
 * templates. All other fields are nullable; supplying null keeps the
 * field empty on the new row (this is NOT a sparse merge — null
 * means "clear the value").
 *
 * AUTHZ in the Final (AdminSessionInterface). Mass-assignment safety:
 * only the shop-info columns are accepted; no path here reaches the
 * non-shop-info dtb_base_info columns (point rate, tax settings, …)
 * which are Phase 2 scope.
 *
 * @link https://schema.org/UpdateAction
 */
#[Be(BaseInfoUpdated::class)]
final readonly class UpdateBaseInfoInput
{
    /**
     * @psalm-taint-source input $shopName
     * @psalm-taint-source input $shopKana
     * @psalm-taint-source input $shopNameEng
     * @psalm-taint-source input $companyName
     * @psalm-taint-source input $postalCode
     * @psalm-taint-source input $pref
     * @psalm-taint-source input $addr01
     * @psalm-taint-source input $addr02
     * @psalm-taint-source input $phoneNumber
     * @psalm-taint-source input $businessHour
     * @psalm-taint-source input $shopEmail01
     * @psalm-taint-source input $shopMessage
     */
    public function __construct(
        public string $shopName,
        public string|null $shopKana = null,
        public string|null $shopNameEng = null,
        public string|null $companyName = null,
        public string|null $postalCode = null,
        public int|null $pref = null,
        public string|null $addr01 = null,
        public string|null $addr02 = null,
        public string|null $phoneNumber = null,
        public string|null $businessHour = null,
        public string|null $shopEmail01 = null,
        public string|null $shopMessage = null,
    ) {
    }
}
