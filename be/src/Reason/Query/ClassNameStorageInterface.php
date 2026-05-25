<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\ClassNameEntity;
use Ray\MediaQuery\Annotation\DbQuery;

/**
 * Admin catalog class names (規格名) — unified Query + Command (Wave 7,
 * same convention as {@see CategoryStorageInterface}).
 *
 *   - list(): every axis, sorted by id for stable display
 *   - item(classNameId): single-row lookup
 *   - put(className): upsert (create / replace)
 *   - delete(classNameId): drop (silent no-op on miss)
 *   - reorder(classNameId, sortNo): generic `doSortNoMove` — rewrites
 *     the storage-only `sort_no` column (not projected onto
 *     {@see ClassNameEntity}). dtb_class_name has no `visible` column,
 *     so the ClassName master carries no `setVisible`.
 */
interface ClassNameStorageInterface
{
    /** @return list<ClassNameEntity> */
    #[DbQuery('tclass_name_list')]
    public function list(): array;

    #[DbQuery('tclass_name_get')]
    public function item(string $classNameId): ClassNameEntity|null;

    #[DbQuery('tclass_name_put')]
    public function put(ClassNameEntity $className): void;

    #[DbQuery('tclass_name_remove')]
    public function delete(string $classNameId): void;

    #[DbQuery('tclass_name_reorder')]
    public function reorder(string $classNameId, int $sortNo): void;
}
