<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Query\AdminMasterRegistryInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Master data selected — Final, the rows of the chosen master
 * (doSelectMasterData).
 *
 *   SelectMasterDataInput → MasterDataSelected   (Direct, idempotent)
 *
 * AUTHZ: no admin session → UnauthorizedAdminAccessException (403). Rows
 * are read through the existing {@see AdminMasterRegistryInterface}; the
 * master type was already constrained to the known set by the Semantic.
 */
final readonly class MasterDataSelected
{
    public string $masterType;

    /** @var list<array{id: string, name: string}> */
    public array $rows;

    public function __construct(
        #[Input] string $masterType,
        #[Inject] AdminSession $adminSession,
        #[Inject] AdminMasterRegistryInterface $masters,
    ) {
        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $this->masterType = $masterType;
        $this->rows = $masters->listRows($masterType);
    }
}
