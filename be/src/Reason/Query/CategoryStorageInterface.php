<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\CategoryEntity;
use Ray\MediaQuery\Annotation\DbQuery;

/**
 * Admin catalog categories — unified Query + Command (Wave 7, mirrors
 * the {@see FavoriteStorageInterface} / {@see AddressStorageInterface}
 * convention). One interface for both reads and writes; a Phase 2 CQRS
 * split happens only when the access pattern demands it.
 *
 *   - list(): flat list of every row (UI flattens by sortNo for display)
 *   - item(categoryId): single-row lookup
 *   - put(category): upsert — create on first write, replace on update
 *   - delete(categoryId): drop a row (idempotent — silent no-op on miss)
 */
interface CategoryStorageInterface
{
    /** @return list<CategoryEntity> */
    #[DbQuery('tcategory_list')]
    public function list(): array;

    #[DbQuery('tcategory_get')]
    public function item(string $categoryId): CategoryEntity|null;

    #[DbQuery('tcategory_put')]
    public function put(CategoryEntity $category): void;

    #[DbQuery('tcategory_remove')]
    public function delete(string $categoryId): void;
}
