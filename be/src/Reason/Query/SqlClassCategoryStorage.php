<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\ClassCategoryEntity;
use Override;

use function ctype_digit;

final class SqlClassCategoryStorage implements ClassCategoryStorageInterface
{
    public function __construct(private readonly InternalDbQueryInterface $db) {}

    /** @return list<ClassCategoryEntity> */
    #[Override]
    public function listByClassName(string $classNameId): array
    {
        if (! ctype_digit($classNameId)) {
            return [];
        }
        return array_map($this->hydrate(...), $this->db->tclass_category_list_by_class_name(classNameId: (int) $classNameId));
    }

    /** @return list<ClassCategoryEntity> */
    #[Override]
    public function list(): array
    {
        return array_map($this->hydrate(...), $this->db->tclass_category_list());
    }

    #[Override]
    public function getById(string $classCategoryId): ClassCategoryEntity|null
    {
        if (! ctype_digit($classCategoryId)) {
            return null;
        }
        $row = $this->db->tclass_category_get(id: (int) $classCategoryId);
        return $row === null ? null : $this->hydrate($row);
    }

    #[Override]
    public function put(ClassCategoryEntity $classCategory): void
    {
        if (! ctype_digit($classCategory->classCategoryId) || ! ctype_digit($classCategory->classNameId)) {
            return;
        }
        $id = (int) $classCategory->classCategoryId;
        $classNameId = (int) $classCategory->classNameId;
        if ($this->db->tclass_category_exists(id: $id) !== null) {
            $this->db->tclass_category_update(id: $id, classNameId: $classNameId, name: $classCategory->name);
            return;
        }
        $sort = (int) ($this->db->tclass_category_next_sort(classNameId: $classNameId)['next_sort'] ?? 1);
        $this->db->tclass_category_insert(id: $id, classNameId: $classNameId, name: $classCategory->name, sortNo: $sort);
    }

    #[Override]
    public function remove(string $classCategoryId): void
    {
        if (ctype_digit($classCategoryId)) {
            $this->db->tclass_category_delete(id: (int) $classCategoryId);
        }
    }

    #[Override]
    public function reorder(string $classCategoryId, int $sortNo): void
    {
        if (ctype_digit($classCategoryId)) {
            $this->db->tclass_category_reorder(id: (int) $classCategoryId, sortNo: $sortNo);
        }
    }

    #[Override]
    public function setVisible(string $classCategoryId, bool $visible): void
    {
        if (ctype_digit($classCategoryId)) {
            $this->db->tclass_category_visible(id: (int) $classCategoryId, visible: (int) $visible);
        }
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): ClassCategoryEntity
    {
        return new ClassCategoryEntity((string) (int) $row['id'], (string) (int) $row['class_name_id'], (string) $row['name']);
    }
}
