<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\ClassCategoryEntity;
use Override;

use function array_values;
use function ksort;

/**
 * In-memory ClassCategory store. Starts empty — tests seed via POST.
 * Singleton so reads see same-request writes.
 */
final class FakeClassCategoryStorage implements ClassCategoryStorageInterface
{
    /** @var array<string, ClassCategoryEntity> keyed by classCategoryId */
    private array $byId = [];

    /** @return list<ClassCategoryEntity> */
    #[Override]
    public function listByClassName(string $classNameId): array
    {
        $out = [];
        foreach ($this->byId as $row) {
            if ($row->classNameId === $classNameId) {
                $out[] = $row;
            }
        }

        return $out;
    }

    /** @return list<ClassCategoryEntity> */
    #[Override]
    public function list(): array
    {
        $rows = $this->byId;
        ksort($rows);

        return array_values($rows);
    }

    #[Override]
    public function getById(string $classCategoryId): ClassCategoryEntity|null
    {
        return $this->byId[$classCategoryId] ?? null;
    }

    #[Override]
    public function put(ClassCategoryEntity $classCategory): void
    {
        $this->byId[$classCategory->classCategoryId] = $classCategory;
    }

    #[Override]
    public function remove(string $classCategoryId): void
    {
        unset($this->byId[$classCategoryId]);
    }
}
