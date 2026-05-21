<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;

/**
 * Opaque master-row identifier for the generic admin-list transitions
 * (`doSortNoMove` / `doToggleVisible`).
 *
 * Each admin master keys its rows differently — dtb_payment / dtb_tag
 * use an int PK, the Fake stores use opaque string handles — so this
 * Semantic is a type assertion only. The actual resolution (and the
 * 404 on a miss) happens in
 * {@see \MyVendor\BeMart\Be\Reason\Query\AdminMasterRegistryInterface}.
 */
final class RowId
{
    #[Validate]
    public function validate(string $rowId): void
    {
        // Type assertion only — the registry resolves the id per master.
    }
}
