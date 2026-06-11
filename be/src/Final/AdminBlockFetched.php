<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\BlockNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Query\BlockStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Admin block fetched — Final, single-row edit view (Wave 9).
 */
final readonly class AdminBlockFetched
{
    public string $blockId;
    public string $blockName;
    public string $blockFileName;
    public bool $blockDeletable;

    public function __construct(
        #[Input] string $blockId,
        #[Inject] AdminSession $adminSession,
        #[Inject] BlockStorageInterface $blocks,
    ) {
        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $row = $blocks->item($blockId);
        if ($row === null) {
            throw new BlockNotFoundException();
        }

        $this->blockId = $row->blockId;
        $this->blockName = $row->blockName;
        $this->blockFileName = $row->blockFileName;
        $this->blockDeletable = $row->blockDeletable;
    }
}
