<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Entity\BlockEntity;
use MyVendor\BeMart\Be\Reason\Query\BlockStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use Ray\Di\Di\Inject;

use function array_map;
use function count;

/**
 * Admin block list fetched — Final (Wave 9).
 */
final readonly class AdminBlockListFetched
{
    public int $count;

    /** @var list<array{blockId: string, blockName: string, blockFileName: string, blockDeletable: bool}> */
    public array $blocks;

    public function __construct(
        #[Inject] AdminSession $adminSession,
        #[Inject] BlockStorageInterface $blocks,
    ) {
        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $rows = $blocks->list();

        $this->count = count($rows);
        $this->blocks = array_map(
            static fn (BlockEntity $row): array => [
                'blockId' => $row->blockId,
                'blockName' => $row->blockName,
                'blockFileName' => $row->blockFileName,
                'blockDeletable' => $row->blockDeletable,
            ],
            $rows,
        );
    }
}
