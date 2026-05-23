<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\PageEntity;
use Ray\MediaQuery\Annotation\DbQuery;

/**
 * Admin CMS pages — unified Query + Command (Wave 9 CMS slice, mirrors
 * the {@see CategoryStorageInterface} / {@see ClassNameStorageInterface}
 * convention).
 *
 *   - list(): flat list of every row (UI orders by pageId for display)
 *   - getById(pageId): single-row lookup
 *   - put(page): upsert — create on first write, replace on update
 *   - remove(pageId): drop a row (idempotent — silent no-op on miss)
 */
interface PageStorageInterface
{
    /** @return list<PageEntity> */
    #[DbQuery('tpage_list', factory: PageEntity::class)]
    public function list(): array;

    #[DbQuery('tpage_get', factory: PageEntity::class)]
    public function getById(string $pageId): PageEntity|null;

    #[DbQuery('tpage_put')]
    public function put(PageEntity $page): void;

    #[DbQuery('tpage_remove')]
    public function remove(string $pageId): void;
}
