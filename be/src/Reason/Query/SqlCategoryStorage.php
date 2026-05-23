<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\CategoryEntity;
use Override;

use function ctype_digit;

final class SqlCategoryStorage implements CategoryStorageInterface
{
    public function __construct(private readonly InternalDbQueryInterface $db) {}

    /** @return list<CategoryEntity> */
    #[Override]
    public function list(): array
    {
        return array_map($this->hydrate(...), $this->db->tcategory_list());
    }

    #[Override]
    public function getById(string $categoryId): CategoryEntity|null
    {
        if (! ctype_digit($categoryId)) {
            return null;
        }
        $row = $this->db->tcategory_get(id: (int) $categoryId);
        return $row === null ? null : $this->hydrate($row);
    }

    #[Override]
    public function put(CategoryEntity $category): void
    {
        if (! ctype_digit($category->categoryId)) {
            return;
        }
        $id = (int) $category->categoryId;
        $parentId = ($category->parentId !== null && ctype_digit($category->parentId)) ? (int) $category->parentId : null;
        $hierarchy = 1;
        if ($parentId !== null) {
            $parent = $this->db->tcategory_parent_hierarchy(id: $parentId);
            $hierarchy = ((int) ($parent['hierarchy'] ?? 0)) + 1;
        }
        if ($this->db->tcategory_exists(id: $id) === null) {
            $this->db->tcategory_insert(id: $id, categoryName: $category->categoryName, parentId: $parentId, sortNo: $category->sortNo, hierarchy: $hierarchy);

            return;
        }

        $this->db->tcategory_update(id: $id, categoryName: $category->categoryName, parentId: $parentId, sortNo: $category->sortNo, hierarchy: $hierarchy);
    }

    #[Override]
    public function remove(string $categoryId): void
    {
        if (! ctype_digit($categoryId)) {
            return;
        }
        $id = (int) $categoryId;
        $this->db->tcategory_product_delete(id: $id);
        $this->db->tcategory_delete(id: $id);
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): CategoryEntity
    {
        return new CategoryEntity(
            categoryId: (string) (int) $row['id'],
            categoryName: (string) ($row['category_name'] ?? ''),
            parentId: $row['parent_category_id'] === null ? null : (string) (int) $row['parent_category_id'],
            sortNo: (int) $row['sort_no'],
        );
    }
}
