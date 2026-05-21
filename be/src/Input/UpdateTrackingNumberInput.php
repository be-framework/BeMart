<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\TrackingNumberUpdated;

/**
 * Input for `doUpdateTrackingNumber` — admin sets the shipping tracking
 * number of an order's dtb_shipping row (Phase 3 ALPS-audit
 * remediation).
 *
 *   UpdateTrackingNumberInput → TrackingNumberUpdated
 *                                (Direct, idempotent, admin AUTHZ)
 *
 * Derived from EC-CUBE's `admin_shipping_update_tracking_number` route
 * — the inline single-row counterpart of the CSV bulk import
 * (`doImportShippingCsv`). ALPS marks it `idempotent`: re-sending the
 * same number leaves the row in the same state.
 *
 * Parameter names match their Semantic validators
 * ({@see \MyVendor\BeMart\Be\Semantic\OrderNo},
 * {@see \MyVendor\BeMart\Be\Semantic\TrackingNumber}).
 */
#[Be(TrackingNumberUpdated::class)]
final readonly class UpdateTrackingNumberInput
{
    /**
     * @psalm-taint-source input $orderNo
     * @psalm-taint-source input $trackingNumber
     */
    public function __construct(
        public string $orderNo,
        public string $trackingNumber,
    ) {
    }
}
