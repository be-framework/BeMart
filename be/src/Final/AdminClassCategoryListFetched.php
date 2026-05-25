<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Entity\ClassCategoryEntity;
use MyVendor\BeMart\Be\Reason\Query\ClassCategoryStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

use function array_map;
use function count;

/**
 * Admin class-category list fetched — Final, the back-office view of
 * every variant VALUE (or the values under one axis when
 * classNameId is set). (Wave 7).
 *
 *   GetAdminClassCategoryListInput → AdminClassCategoryListFetched
 *     (Direct, safe read, admin AUTHZ)
 */
final readonly class AdminClassCategoryListFetched
{
    public string|null $classNameId;
    public int $count;

    /** @var list<array{classCategoryId: string, classNameId: string, name: string}> */
    public array $classCategories;

    public function __construct(
        #[Input] string|null $classNameId,
        #[Inject] AdminSessionInterface $adminSession,
        #[Inject] ClassCategoryStorageInterface $classCategories,
    ) {
        if ($adminSession->adminId() === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $rows = $classNameId === null
            ? $classCategories->list()
            : $classCategories->listByClassName($classNameId);

        $this->classNameId = $classNameId;
        $this->count = count($rows);
        $this->classCategories = array_map(
            static fn (ClassCategoryEntity $row): array => [
                'classCategoryId' => $row->classCategoryId,
                'classNameId' => $row->classNameId,
                'name' => $row->name,
            ],
            $rows,
        );
    }
}
