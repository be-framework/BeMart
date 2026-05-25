<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\ClassCategoryEntity;
use Ray\MediaQuery\Annotation\DbQuery;

/**
 * Admin catalog class categories (規格分類) — unified Query + Command
 * (Wave 7, same convention as the two sibling storages).
 *
 *   - listByClassName(classNameId): scoped to one axis (UI default view)
 *   - list(): every row regardless of axis (admin grid)
 *   - item(classCategoryId): single-row lookup
 *   - put(classCategory): upsert
 *   - delete(classCategoryId): drop (silent no-op on miss)
 *   - reorder(classCategoryId, sortNo): generic `doSortNoMove` —
 *     rewrites the storage-only `sort_no` column.
 *   - setVisible(classCategoryId, visible): generic `doToggleVisible`
 *     — rewrites the storage-only `visible` column. Both columns exist
 *     on dtb_class_category but are outside the {@see ClassCategoryEntity}
 *     projection; the two operations edit them directly.
 */
interface ClassCategoryStorageInterface
{
    /** @return list<ClassCategoryEntity> */
    #[DbQuery('tclass_category_list_by_class_name')]
    public function listByClassName(string $classNameId): array;

    /** @return list<ClassCategoryEntity> */
    #[DbQuery('tclass_category_list')]
    public function list(): array;

    #[DbQuery('tclass_category_get')]
    public function item(string $classCategoryId): ClassCategoryEntity|null;

    #[DbQuery('tclass_category_put')]
    public function put(ClassCategoryEntity $classCategory): void;

    #[DbQuery('tclass_category_delete')]
    public function delete(string $classCategoryId): void;

    #[DbQuery('tclass_category_reorder')]
    public function reorder(string $classCategoryId, int $sortNo): void;

    #[DbQuery('tclass_category_visible')]
    public function setVisible(string $classCategoryId, bool $visible): void;
}
