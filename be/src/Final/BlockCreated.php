<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Entity\BlockEntity;
use MyVendor\BeMart\Be\Reason\Query\BlockStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Service\BlockIdGeneratorInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Block created — Final (Wave 9). Admin-authored blocks are always
 * blockDeletable=true.
 */
final readonly class BlockCreated
{
    public string $blockId;
    public string $blockName;
    public string $blockFileName;
    public bool $blockDeletable;

    public function __construct(
        #[Input] string $blockName,
        #[Input] string $blockFileName,
        #[Inject] AdminSessionInterface $adminSession,
        #[Inject] BlockStorageInterface $blocks,
        #[Inject] BlockIdGeneratorInterface $idGenerator,
    ) {
        if ($adminSession->adminId() === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $entity = new BlockEntity(
            blockId: $idGenerator->generate()->value(),
            blockName: $blockName,
            blockFileName: $blockFileName,
            blockDeletable: true,
        );

        $blocks->put($entity);

        $this->blockId = $entity->blockId;
        $this->blockName = $entity->blockName;
        $this->blockFileName = $entity->blockFileName;
        $this->blockDeletable = $entity->blockDeletable;
    }
}
