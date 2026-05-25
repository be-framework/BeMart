<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\TagEntity;

/**
 * Admin product tags — unified Query + Command (Wave 9).
 */
interface TagStorageInterface
{
    /** @return list<TagEntity> */
    public function list(): array;

    public function getById(string $tagId): TagEntity|null;

    public function put(TagEntity $tag): void;

    public function remove(string $tagId): void;
}
