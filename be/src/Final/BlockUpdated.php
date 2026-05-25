<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\BlockNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Entity\BlockEntity;
use MyVendor\BeMart\Be\Reason\Query\BlockStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Block updated — Final (Wave 9, idempotent).
 */
final readonly class BlockUpdated
{
    public string $blockId;
    public string $blockName;
    public string $blockFileName;
    public bool $blockDeletable;

    public function __construct(
        #[Input] string $blockId,
        #[Input] string|null $blockName,
        #[Input] string|null $blockFileName,
        #[Inject] AdminSessionInterface $adminSession,
        #[Inject] BlockStorageInterface $blocks,
    ) {
        if ($adminSession->adminId() === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $current = $blocks->getById($blockId);
        if ($current === null) {
            throw new BlockNotFoundException();
        }

        $merged = new BlockEntity(
            blockId: $current->blockId,
            blockName: $blockName ?? $current->blockName,
            blockFileName: $blockFileName ?? $current->blockFileName,
            blockDeletable: $current->blockDeletable,
        );

        $blocks->put($merged);

        $this->blockId = $merged->blockId;
        $this->blockName = $merged->blockName;
        $this->blockFileName = $merged->blockFileName;
        $this->blockDeletable = $merged->blockDeletable;
    }
}
