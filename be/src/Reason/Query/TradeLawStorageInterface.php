<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\TradeLawEntity;
use Ray\MediaQuery\Annotation\DbQuery;

/**
 * TradeLaw storage — unified Query + Command (Wave 8).
 *
 * Wave 8 first iteration treats the Specified Commercial Transactions
 * page as a single body blob — get() returns the current full text,
 * update() replaces it. Phase 2 will split into per-item rows so the
 * sort_no / displayOrderScreen toggles per ALPS descriptor can be
 * exercised independently. The interface is forward-declared narrow
 * here so the split is non-breaking.
 */
interface TradeLawStorageInterface
{
    #[DbQuery('ttrade_law_get')]
    public function item(): TradeLawEntity;

    #[DbQuery('ttrade_law_put')]
    public function put(TradeLawEntity $entity): void;
}
