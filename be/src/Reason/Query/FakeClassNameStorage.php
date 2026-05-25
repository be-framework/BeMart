<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\ClassNameEntity;
use Override;

use function array_values;
use function ksort;

/**
 * In-memory ClassName store. Starts empty — tests seed via POST.
 * Singleton so reads see same-request writes (same convention as
 * {@see FakeCategoryStorage}).
 */
final class FakeClassNameStorage implements ClassNameStorageInterface
{
    /** @var array<string, ClassNameEntity> keyed by classNameId */
    private array $byId = [];

    /**
     * Storage-only `sort_no` per row — dtb_class_name has the column
     * but {@see ClassNameEntity} does not project it.
     *
     * @var array<string, int>
     */
    private array $sortNo = [];

    /** @return list<ClassNameEntity> */
    #[Override]
    public function list(): array
    {
        $rows = $this->byId;
        ksort($rows);

        return array_values($rows);
    }

    #[Override]
    public function getById(string $classNameId): ClassNameEntity|null
    {
        return $this->byId[$classNameId] ?? null;
    }

    #[Override]
    public function put(ClassNameEntity $className): void
    {
        $this->byId[$className->classNameId] = $className;
    }

    #[Override]
    public function remove(string $classNameId): void
    {
        unset($this->byId[$classNameId], $this->sortNo[$classNameId]);
    }

    #[Override]
    public function reorder(string $classNameId, int $sortNo): void
    {
        if (! isset($this->byId[$classNameId])) {
            return;
        }

        $this->sortNo[$classNameId] = $sortNo;
    }

    /** Test introspection: the `sort_no` last written for a row. */
    public function sortNoOf(string $classNameId): int|null
    {
        return $this->sortNo[$classNameId] ?? null;
    }
}
