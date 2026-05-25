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
 * Ray.MediaQuery UPDATE against the existing pre-order row.
 */
interface OrderCommandInterface
{
    public function register(FinalizedOrderEntity $order): void;
}
