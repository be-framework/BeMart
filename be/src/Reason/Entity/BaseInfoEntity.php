<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Entity;

/**
 * BaseInfo entity — projection of EC-CUBE 4.3 dtb_base_info, the
 * single-row table that holds shop-wide configuration (shop name,
 * address, contact, return-policy text, etc.).
 *
 * Modeled as a single mutable value-object that admin screens fetch
 * and overwrite as a whole. The doUpdateBaseInfo transition replaces
 * the entire row — there is no per-field PATCH semantic in EC-CUBE.
 *
 * Wave 8 first iteration ships the most-edited fields (shop name +
 * postal/address + contact + free-form shop message). The full
 * dtb_base_info row carries ~30 columns (point rate, tax settings,
 * delivery-free thresholds, mypage order status display flag, …); the
 * remaining fields are Phase 2 scope — same incremental approach as
 * CustomerListFetched's filter scope.
 */
final readonly class BaseInfoEntity implements \Ray\MediaQuery\ToScalarInterface
{
    use MediaQueryJsonEntityTrait;

    public function __construct(
        public string $shopName,
        public string|null $shopKana,
        public string|null $shopNameEng,
        public string|null $companyName,
        public string|null $postalCode,
        public int|null $pref,
        public string|null $addr01,
        public string|null $addr02,
        public string|null $phoneNumber,
        public string|null $businessHour,
        public string|null $shopEmail01,
        public string|null $shopMessage,
    ) {
    }
}
