<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\BlockEntity;

/**
 * Admin CMS blocks — unified Query + Command (Wave 9).
 */
interface BlockStorageInterface
{
    /** @return list<BlockEntity> */
    public function list(): array;

    public function getById(string $blockId): BlockEntity|null;

    public function put(BlockEntity $block): void;

    public function remove(string $blockId): void;
}
