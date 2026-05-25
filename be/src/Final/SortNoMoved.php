<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\MasterRowNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Query\AdminMasterRegistryInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Sort order moved — Final, proof an admin reordered one row of an
 * admin master list (`doSortNoMove`).
 *
 *   SortNoMoveInput → SortNoMoved   (Direct, idempotent)
 *
 * AUTHZ — cross-firewall ladder (same as the rest of the admin Finals):
 *   1. No admin session   → UnauthorizedAdminAccessException  (403)
 *   2. Unknown row        → MasterRowNotFoundException        (404)
 *
 * An unknown `masterType` or a master with no `sort_no` column is
 * caught earlier: the {@see \MyVendor\BeMart\Be\Semantic\MasterType}
 * validator rejects an out-of-set value (400), and the registry's
 * `reorder` raises {@see \MyVendor\BeMart\Be\Exception\MasterOperationNotSupportedException}
 * for a known-but-unsortable master (400).
 *
 * Idempotency: ALPS marks this `idempotent` — re-sending the same
 * (masterType, rowId, sortNo) is a no-op-equivalent (the row already
 * holds that sort_no).
 */
final readonly class SortNoMoved
{
    public string $masterType;
    public string $rowId;
    public int $sortNo;

    public function __construct(
        #[Input] string $masterType,
        #[Input] string $rowId,
        #[Input] int $sortNo,
        #[Inject] AdminSession $adminSession,
        #[Inject] AdminMasterRegistryInterface $masters,
    ) {
        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        if (! $masters->rowExists($masterType, $rowId)) {
            throw new MasterRowNotFoundException();
        }

        $masters->reorder($masterType, $rowId, $sortNo);

        $this->masterType = $masterType;
        $this->rowId = $rowId;
        $this->sortNo = $sortNo;
    }
}
