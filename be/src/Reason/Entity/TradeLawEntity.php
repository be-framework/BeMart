<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Entity;

/**
 * TradeLaw entity — projection of EC-CUBE 4.3 dtb_trade_law row.
 *
 * Specified Commercial Transactions Act ("特定商取引法") page —
 * required by Japanese e-commerce regulation. The page is a list of
 * (name, description) pairs (item name + the body text explaining
 * that item). The admin screen edits the whole list as a unit; in
 * the migration scope we model it as a single "blob" — the
 * TradeLawStorageInterface::get() returns the current full body
 * and update() replaces it.
 *
 * Phase 2 will split this into per-item rows so the sort_no /
 * displayOrderScreen toggles per ALPS descriptor can be exercised
 * independently. For now the single-blob shape is enough for the
 * doUpdateTradeLaw transition.
 */
final readonly class TradeLawEntity implements \Ray\MediaQuery\ToScalarInterface
{
    use MediaQueryJsonEntityTrait;

    public function __construct(
        public string $body,
    ) {
    }
}
