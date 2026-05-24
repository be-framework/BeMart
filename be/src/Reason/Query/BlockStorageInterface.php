<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\BlockEntity;
use Ray\MediaQuery\Annotation\DbQuery;

/**
 * Admin CMS blocks — unified Query + Command (Wave 9).
 */
interface BlockStorageInterface
{
    /** @return list<BlockEntity> */
    #[DbQuery('tblock_list')]
    public function list(): array;

    #[DbQuery('tblock_get')]
    public function item(string $blockId): BlockEntity|null;

    #[DbQuery('tblock_put')]
    public function put(BlockEntity $block): void;

    #[DbQuery('tblock_remove')]
    public function delete(string $blockId): void;
}
