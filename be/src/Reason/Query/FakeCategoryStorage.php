<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\CategoryEntity;
use Override;

use function array_values;
use function usort;

/**
 * In-memory category store. Starts empty (no JSON fixture) — tests
 * seed by POSTing to the admin endpoint. Singleton-bound so a request's
 * reads see its writes within the same Becoming chain.
 *
 * The list projection is sorted by (sortNo asc, categoryId asc) so a
 * caller can rely on a stable display order without sorting again.
 */
final class FakeCategoryStorage implements CategoryStorageInterface
{
    /** @var array<string, CategoryEntity> keyed by categoryId */
    private array $byId = [];

    /** @return list<CategoryEntity> */
    #[Override]
    public function list(): array
    {
        $rows = array_values($this->byId);
        usort($rows, static function (CategoryEntity $a, CategoryEntity $b): int {
            return $a->sortNo <=> $b->sortNo ?: $a->categoryId <=> $b->categoryId;
        });

        return $rows;
    }

    #[Override]
    public function getById(string $categoryId): CategoryEntity|null
    {
        return $this->byId[$categoryId] ?? null;
    }

    #[Override]
    public function put(CategoryEntity $category): void
    {
        $this->byId[$category->categoryId] = $category;
    }

    #[Override]
    public function remove(string $categoryId): void
    {
        unset($this->byId[$categoryId]);
    }
}
