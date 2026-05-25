<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\ShippingAddressEntity;
use MyVendor\BeMart\Be\Reason\Query\Result\ShippingTrackingNumber;
use Ray\MediaQuery\Annotation\DbQuery;

/**
 * Shipping-address persistence contract — Wave 9η.
 *
 * Single-row-per-order store keyed by `orderNo`. The admin-side flows
 * (doSelectShippingAddress, doUpdateShippingAddress) both write here;
 * the existence-checks pre-dispatch happen via {@see OrderQueryInterface}
 * (so a request that targets an unknown orderNo is rejected before
 * touching the address store).
 *
 * The `trackingNumber` column of dtb_shipping is edited separately
 * (`doUpdateTrackingNumber`) — it is NOT part of the address-field set
 * {@see ShippingAddressEntity} models (a tracking number is shipment
 * fulfilment metadata, not a delivery target), so it gets its own
 * narrow read/write pair below.
 *
 * Phase 2 swaps for a Ray.MediaQuery mapping over dtb_shipping rows.
 */
interface ShippingAddressStorageInterface
{
    #[DbQuery('shipping_get_by_order_no', factory: ShippingAddressEntity::class)]
    public function getByOrderNo(string $orderNo): ShippingAddressEntity|null;

    #[DbQuery('shipping_put')]
    public function put(ShippingAddressEntity $address): void;

    /**
     * Wave 9η (goExportShipping): full dump for the CSV downloader.
     * Order is undefined; consumers sort if they need stability.
     *
     * @return list<ShippingAddressEntity>
     */
    #[DbQuery('shipping_list_all', factory: ShippingAddressEntity::class)]
    public function listAll(): array;

    /**
     * `doUpdateTrackingNumber` — write the shipping tracking number of
     * the order's dtb_shipping row. A miss (no shipping row for the
     * orderNo) is a silent no-op, same shape as `put`.
     */
    #[DbQuery('shipping_update_tracking')]
    public function updateTrackingNumber(string $orderNo, string $trackingNumber): void;

    /**
     * Read the tracking number last written for an order. Returns null
     * when the order has no shipping row, or has one with no tracking
     * number set. Lets the `doUpdateTrackingNumber` Final echo back the
     * persisted value.
     */
    #[DbQuery('shipping_tracking_by_order_no')]
    public function trackingNumberByOrderNo(string $orderNo): ShippingTrackingNumber;
}
