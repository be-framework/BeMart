<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\CategoryEntity;

/**
 * Admin catalog categories — unified Query + Command (Wave 7, mirrors
 * the {@see FavoriteStorageInterface} / {@see AddressStorageInterface}
 * convention). One interface for both reads and writes; a Phase 2 CQRS
 * split happens only when the access pattern demands it.
 *
 *   - list(): flat list of every row (UI flattens by sortNo for display)
 *   - getById(categoryId): single-row lookup
 *   - put(category): upsert — create on first write, replace on update
 *   - remove(categoryId): drop a row (idempotent — silent no-op on miss)
 */
interface CategoryStorageInterface
{
    /** @return list<CategoryEntity> */
    public function list(): array;

    public function getById(string $categoryId): CategoryEntity|null;

    public function put(CategoryEntity $category): void;

    public function remove(string $categoryId): void;
}
