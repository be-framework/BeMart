<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\PageEntity;

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
    public function list(): array;

    public function getById(string $pageId): PageEntity|null;

    public function put(PageEntity $page): void;

    public function remove(string $pageId): void;
}
