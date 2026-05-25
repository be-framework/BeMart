<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\ClassNameEntity;

/**
 * Admin catalog class names (規格名) — unified Query + Command (Wave 7,
 * same convention as {@see CategoryStorageInterface}).
 *
 *   - list(): every axis, sorted by id for stable display
 *   - getById(classNameId): single-row lookup
 *   - put(className): upsert (create / replace)
 *   - remove(classNameId): drop (silent no-op on miss)
 */
interface ClassNameStorageInterface
{
    /** @return list<ClassNameEntity> */
    public function list(): array;

    public function getById(string $classNameId): ClassNameEntity|null;

    public function put(ClassNameEntity $className): void;

    public function remove(string $classNameId): void;
}
