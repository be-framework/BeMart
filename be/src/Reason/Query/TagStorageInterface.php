<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\TagEntity;

/**
 * Admin product tags — unified Query + Command (Wave 9).
 *
 * `reorder` implements the generic `doSortNoMove` transition for the
 * Tag master: it rewrites the `sort_no` column of one row. dtb_tag is
 * the EC-CUBE 4.3 table; sort_no is storage-only (not projected onto
 * {@see TagEntity}). A miss is a silent no-op — same shape as `put` /
 * `remove`. dtb_tag has no `visible` column so the Tag master does NOT
 * carry `setVisible` (ALPS attaches `doToggleVisible` only to the
 * masters that have one).
 */
interface TagStorageInterface
{
    /** @return list<TagEntity> */
    public function list(): array;

    public function getById(string $tagId): TagEntity|null;

    public function put(TagEntity $tag): void;

    public function remove(string $tagId): void;

    public function reorder(string $tagId, int $sortNo): void;
}
