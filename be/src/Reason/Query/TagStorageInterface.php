<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Service\ChangesProductCorpus;
use MyVendor\BeMart\Be\Reason\Entity\TagEntity;
use Ray\MediaQuery\Annotation\DbQuery;

/**
 * Admin product tags — unified Query + Command (Wave 9).
 *
 * `reorder` implements the generic `doSortNoMove` transition for the
 * Tag master: it rewrites the `sort_no` column of one row. dtb_tag is
 * the EC-CUBE 4.3 table; sort_no is storage-only (not projected onto
 * {@see TagEntity}). A miss is a silent no-op — same shape as `put` /
 * `delete`. dtb_tag has no `visible` column so the Tag master does NOT
 * carry `setVisible` (ALPS attaches `doToggleVisible` only to the
 * masters that have one).
 */
interface TagStorageInterface
{
    /** @return list<TagEntity> */
    #[DbQuery('tag_list')]
    public function list(): array;

    #[DbQuery('tag_get')]
    public function item(string $tagId): TagEntity|null;

    #[DbQuery('tag_put')]
    #[ChangesProductCorpus]
    public function put(TagEntity $tag): void;

    #[DbQuery('tag_delete')]
    #[ChangesProductCorpus]
    public function delete(string $tagId): void;

    #[DbQuery('tag_reorder')]
    #[ChangesProductCorpus]
    public function reorder(string $tagId, int $sortNo): void;
}
