<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\FinalizedOrderEntity;

/**
 * Persists the finalized Order (dtb_order with orderStatus=NEW(1)).
 *
 * In EC-CUBE this is implicit — the pre-order row's columns are mutated by
 * PurchaseFlow and OrderRepository commits the same row. The Pilot 5 Reason
 * makes the commit explicit so the Final's "convergence" is observable
 * (matches Pilot 4 CustomerCommand). Phase 2 will swap the fake for a
 * Ray.MediaQuery command against the existing pre-order row.
 *
 * Wave 7 (admin order management) extends the contract with two
 * administrator-driven mutators:
 *
 *   - `update`        — overwrites the row in place after the admin edits
 *                       editable fields (discount / charge / usePoint).
 *                       The supplied entity carries the merged shape; the
 *                       Final is responsible for protecting non-editable
 *                       fields (orderNo / customerId / total) against
 *                       mass-assignment (Pilot 5 F-2 lesson).
 *   - `updateStatus`  — flips the status column on the row identified by
 *                       `orderNo`. A separate API rather than an `update`
 *                       so the state-machine semantics stay observable
 *                       and the Final does not have to rebuild the full
 *                       entity for a single-field flip.
 */
interface OrderCommandInterface
{
    public function register(FinalizedOrderEntity $order): void;

    /**
     * Overwrite the persisted row for `$order->orderNo` with the supplied
     * entity. Caller is responsible for merging editable fields onto the
     * current row and preserving non-editable fields verbatim.
     */
    public function update(FinalizedOrderEntity $order): void;

    /**
     * Flip the `orderStatus` column on the row identified by `$orderNo`.
     * The fake adapter performs a read-modify-write through the
     * underlying storage. Returns silently if the row no longer exists
     * (the Final guards against that, so the storage-level miss is a
     * concurrent-delete race we treat as a no-op).
     */
    public function updateStatus(string $orderNo, int $newStatus): void;
}
