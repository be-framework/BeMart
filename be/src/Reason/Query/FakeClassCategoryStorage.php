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

    /**
     * Storage-only `sort_no` per row — dtb_class_category has the
     * column but {@see ClassCategoryEntity} does not project it.
     *
     * @var array<string, int>
     */
    private array $sortNo = [];

    /**
     * Storage-only `visible` per row — dtb_class_category has the
     * column but {@see ClassCategoryEntity} does not project it.
     * A row with no entry is considered visible (the schema default).
     *
     * @var array<string, bool>
     */
    private array $visible = [];

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
        unset(
            $this->byId[$classCategoryId],
            $this->sortNo[$classCategoryId],
            $this->visible[$classCategoryId],
        );
    }

    #[Override]
    public function reorder(string $classCategoryId, int $sortNo): void
    {
        if (! isset($this->byId[$classCategoryId])) {
            return;
        }

        $this->sortNo[$classCategoryId] = $sortNo;
    }

    #[Override]
    public function setVisible(string $classCategoryId, bool $visible): void
    {
        if (! isset($this->byId[$classCategoryId])) {
            return;
        }

        $this->visible[$classCategoryId] = $visible;
    }

    /** Test introspection: the `sort_no` last written for a row. */
    public function sortNoOf(string $classCategoryId): int|null
    {
        return $this->sortNo[$classCategoryId] ?? null;
    }

    /**
     * Test introspection: the `visible` flag last written for a row.
     * Defaults to true (schema default) when never toggled.
     */
    public function visibleOf(string $classCategoryId): bool
    {
        return $this->visible[$classCategoryId] ?? true;
    }
}
