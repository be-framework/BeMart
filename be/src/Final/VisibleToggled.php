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
 * Visibility toggled — Final, proof an admin set the `visible` flag of
 * one row of an admin master list (`doToggleVisible`).
 *
 *   ToggleVisibleInput → VisibleToggled   (Direct, idempotent)
 *
 * AUTHZ — cross-firewall ladder (same as the rest of the admin Finals):
 *   1. No admin session   → UnauthorizedAdminAccessException  (403)
 *   2. Unknown row        → MasterRowNotFoundException        (404)
 *
 * An unknown `masterType` is rejected by the
 * {@see \MyVendor\BeMart\Be\Semantic\MasterType} validator (400); a
 * known master with no `visible` column (tag / className) raises
 * {@see \MyVendor\BeMart\Be\Exception\MasterOperationNotSupportedException}
 * from the registry (400).
 *
 * Idempotency: ALPS marks this `idempotent` — the flag is set to an
 * explicit value, so a replay with the same `visible` is a no-op.
 */
final readonly class VisibleToggled
{
    public string $masterType;
    public string $rowId;
    public bool $visible;

    public function __construct(
        #[Input] string $masterType,
        #[Input] string $rowId,
        #[Input] bool $visible,
        #[Inject] AdminSession $adminSession,
        #[Inject] AdminMasterRegistryInterface $masters,
    ) {
        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        if (! $masters->rowExists($masterType, $rowId)) {
            throw new MasterRowNotFoundException();
        }

        $masters->setVisible($masterType, $rowId, $visible);

        $this->masterType = $masterType;
        $this->rowId = $rowId;
        $this->visible = $visible;
    }
}
