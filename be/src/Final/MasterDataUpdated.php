<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Service\MasterDataWriterInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Master data updated — Final, proof an admin bulk-saved a master's rows
 * (doUpdateMasterData).
 *
 *   UpdateMasterDataInput → MasterDataUpdated   (Direct, idempotent)
 *
 * AUTHZ: no admin session → UnauthorizedAdminAccessException (403). The
 * destructive bulk write is delegated to {@see MasterDataWriterInterface}.
 */
final readonly class MasterDataUpdated
{
    public string $masterType;
    public int $count;

    /**
     * @param list<array{id: string, name: string, sortNo?: int}> $rows
     */
    public function __construct(
        #[Input] string $masterType,
        #[Input] array $rows,
        #[Inject] AdminSession $adminSession,
        #[Inject] MasterDataWriterInterface $writer,
    ) {
        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $this->masterType = $masterType;
        $this->count = $writer->update($masterType, $rows);
    }
}
