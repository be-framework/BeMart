<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\ClassNameNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Query\ClassCategoryStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\ClassNameStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Class name deleted — Final, proof one axis was removed (Wave 7).
 *
 *   DeleteClassNameInput → ClassNameDeleted (Direct, idempotent)
 *
 * ALPS doc note: this BeMart slice deletes child ClassCategory rows before
 * removing the ClassName axis, matching the already-published admin delete
 * affordance while keeping SQL storage to single statements per command.
 * ProductClass dependency guards remain a follow-up because product-class
 * lifecycle completion is still under Web+DB evidence.
 */
final readonly class ClassNameDeleted
{
    public string $classNameId;

    public function __construct(
        #[Input] string $classNameId,
        #[Inject] AdminSession $adminSession,
        #[Inject] ClassCategoryStorageInterface $classCategories,
        #[Inject] ClassNameStorageInterface $classNames,
    ) {
        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        if ($classNames->item($classNameId) === null) {
            throw new ClassNameNotFoundException();
        }

        foreach ($classCategories->listByClassName($classNameId) as $classCategory) {
            $classCategories->delete($classCategory->classCategoryId);
        }

        $classNames->delete($classNameId);

        $this->classNameId = $classNameId;
    }
}
