<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\ShippingAddressEntity;

/**
 * Shipping-address persistence contract — Wave 9η.
 *
 * Single-row-per-order store keyed by `orderNo`. The admin-side flows
 * (doSelectShippingAddress, doUpdateShippingAddress) both write here;
 * the existence-checks pre-dispatch happen via {@see OrderQueryInterface}
 * (so a request that targets an unknown orderNo is rejected before
 * touching the address store).
 *
 * Phase 2 swaps for a Ray.MediaQuery mapping over dtb_shipping rows.
 */
interface ShippingAddressStorageInterface
{
    public function getByOrderNo(string $orderNo): ShippingAddressEntity|null;

    public function put(ShippingAddressEntity $address): void;

    /**
     * Wave 9η (goExportShipping): full dump for the CSV downloader.
     * Order is undefined; consumers sort if they need stability.
     *
     * @return list<ShippingAddressEntity>
     */
    public function listAll(): array;
}
