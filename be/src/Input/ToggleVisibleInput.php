<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\VisibleToggled;

/**
 * Input for `doToggleVisible` — the generic admin-list visibility
 * transition (Phase 3 ALPS-audit remediation).
 *
 *   ToggleVisibleInput → VisibleToggled   (Direct, idempotent, admin AUTHZ)
 *
 * EC-CUBE exposes a per-master *_visible / *_visibility route for each
 * admin list screen (Payment / Delivery / ClassCategory / News). BeMart
 * folds them into one transition: `masterType` names the master, `rowId`
 * the row, `visible` the explicit target state. ALPS marks the
 * operation `idempotent` — it sets the flag to an explicit value rather
 * than blind-flipping it, so re-sending the same value is a no-op.
 *
 * Parameter names match their Semantic validators
 * ({@see \MyVendor\BeMart\Be\Semantic\MasterType},
 * {@see \MyVendor\BeMart\Be\Semantic\Visible}).
 */
#[Be(VisibleToggled::class)]
final readonly class ToggleVisibleInput
{
    /**
     * @psalm-taint-source input $masterType
     * @psalm-taint-source input $rowId
     * @psalm-taint-source input $visible
     */
    public function __construct(
        public string $masterType,
        public string $rowId,
        public bool $visible,
    ) {
    }
}
