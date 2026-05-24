<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\BlockNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Query\BlockStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Block deleted — Final (Wave 9, idempotent).
 *
 * System blocks (blockDeletable=false) cannot be removed; the storage
 * masks this as "not found" (404) for the first iteration. Phase 2
 * will expose a dedicated guard exception once a real consumer asks.
 */
final readonly class BlockDeleted
{
    public string $blockId;

    public function __construct(
        #[Input] string $blockId,
        #[Inject] AdminSessionInterface $adminSession,
        #[Inject] BlockStorageInterface $blocks,
    ) {
        if ($adminSession->adminId() === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $row = $blocks->item($blockId);
        if ($row === null || ! $row->blockDeletable) {
            throw new BlockNotFoundException();
        }

        $blocks->delete($blockId);

        $this->blockId = $blockId;
    }
}
