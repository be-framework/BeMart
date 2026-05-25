<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\AdminOrderUpdated;

/**
 * Input for doUpdateOrder — admin edits a finalized order in place.
 *
 *   AdminUpdateOrderInput → AdminOrderUpdated  (Direct, idempotent)
 *
 * AUTHZ — cross-firewall (Wave 4 lesson): the Final pulls the adminId
 * from {@see \MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface}.
 * No admin session → 403. Unknown orderNo → 404 (OrderNotFoundException).
 *
 * Mass-assignment safety (Pilot 5 F-2 lesson) — admin variant of the
 * pattern Pilot 8 applies to customer self-edit:
 *
 *   - `orderNo`     IS in the body — admin needs a way to pick WHICH
 *                   order to update. It is the target selector. (The
 *                   customer-self equivalent of Pilot 8 hides the
 *                   customerId; here we hide the adminId via session,
 *                   and surface the target orderNo via input.)
 *   - `customerId`  is INTENTIONALLY ABSENT — re-parenting an order to
 *                   another customer is NOT an editable operation in
 *                   this transition. The Final reuses the persisted
 *                   value verbatim.
 *   - `total`       is INTENTIONALLY ABSENT — it is a DERIVED field
 *                   (subtotal + tax + deliveryFeeTotal + charge − discount
 *                   in EC-CUBE; the migration's PurchaseFlow recompute is
 *                   out of scope for Phase 1). Surfacing it would let an
 *                   admin "pay" any amount they like; preserving the
 *                   persisted value here keeps that audit trail intact.
 *   - `paymentTotal`, `subtotal`, `tax`, `addPoint`, `orderStatus`,
 *     `orderDate`, `paymentDate`, `preOrderId`, `deliveryFeeTotal`,
 *     `paymentMethodId` are ALSO absent — same reasoning. Each is
 *     either a derived total (Phase 2 PurchaseFlow recompute), a
 *     historical timestamp, an immutable foreign key, or governed by
 *     its own dedicated transition (`doUpdateOrderStatus` for status,
 *     `doSendOrderMail` for paymentDate-related flows, etc.).
 *
 * Editable fields (Wave 7 first iteration): the three knobs the admin
 * panel touches most often — discount adjustments, payment surcharge
 * fixes, and used-points fixes. Each is nullable; null leaves the
 * persisted value untouched (Pilot 8 partial-update convention).
 *
 * Idempotency: a PUT with the same body returns the same projection
 * (no-op when the merged shape is identical to the persisted shape).
 * The Final does not short-circuit on equality — it always calls
 * `OrderCommandInterface::update`, but the operation is the same
 * overwrite either way.
 *
 * @link https://schema.org/UpdateAction
 */
#[Be(AdminOrderUpdated::class)]
final readonly class AdminUpdateOrderInput
{
    /**
     * Wave 7: every field is admin-form input — same taint discipline as
     * the customer-side UpdateCustomerInput.
     *
     * @psalm-taint-source input $orderNo
     * @psalm-taint-source input $discount
     * @psalm-taint-source input $charge
     * @psalm-taint-source input $usePoint
     */
    public function __construct(
        public string $orderNo,
        public int|null $discount = null,
        public int|null $charge = null,
        public int|null $usePoint = null,
    ) {
    }
}
