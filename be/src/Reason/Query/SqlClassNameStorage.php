<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\ClassNameEntity;
use Override;

use function ctype_digit;

final class SqlClassNameStorage implements ClassNameStorageInterface
{
    public function __construct(private readonly InternalDbQueryInterface $db) {}

    /** @return list<ClassNameEntity> */
    #[Override]
    public function list(): array
    {
        return array_map($this->hydrate(...), $this->db->tclass_name_list());
    }

    #[Override]
    public function getById(string $classNameId): ClassNameEntity|null
    {
        if (! ctype_digit($classNameId)) {
            return null;
        }
        $row = $this->db->tclass_name_get(id: (int) $classNameId);
        return $row === null ? null : $this->hydrate($row);
    }

    #[Override]
    public function put(ClassNameEntity $className): void
    {
        if (! ctype_digit($className->classNameId)) {
            return;
        }
        $id = (int) $className->classNameId;
        if ($this->db->tclass_name_exists(id: $id) !== null) {
            $this->db->tclass_name_update(id: $id, name: $className->name);
            return;
        }
        $sort = (int) ($this->db->tclass_name_next_sort()['next_sort'] ?? 1);
        $this->db->tclass_name_insert(id: $id, name: $className->name, sortNo: $sort);
    }

    #[Override]
    public function remove(string $classNameId): void
    {
        if (! ctype_digit($classNameId)) {
            return;
        }
        $id = (int) $classNameId;
        $this->db->tclass_name_children_delete(id: $id);
        $this->db->tclass_name_delete(id: $id);
    }

    #[Override]
    public function reorder(string $classNameId, int $sortNo): void
    {
        if (ctype_digit($classNameId)) {
            $this->db->tclass_name_reorder(id: (int) $classNameId, sortNo: $sortNo);
        }
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): ClassNameEntity
    {
        return new ClassNameEntity((string) (int) $row['id'], (string) $row['name']);
    }
}
