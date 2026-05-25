<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\ClassCategoryNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Query\ClassCategoryStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Class category deleted — Final, proof one variant value was removed
 * (Wave 7).
 *
 *   DeleteClassCategoryInput → ClassCategoryDeleted (Direct,
 *                                                    idempotent)
 *
 * ALPS doc note: production EC-CUBE refuses deletion when referencing
 * ProductClass rows exist. The in-memory store does NOT enforce that
 * guard in the first iteration — same Phase 2 deferral as
 * {@see ClassNameDeleted}.
 */
final readonly class ClassCategoryDeleted
{
    public string $classCategoryId;

    public function __construct(
        #[Input] string $classCategoryId,
        #[Inject] AdminSessionInterface $adminSession,
        #[Inject] ClassCategoryStorageInterface $classCategories,
    ) {
        if ($adminSession->adminId() === null) {
            throw new UnauthorizedAdminAccessException();
        }

        if ($classCategories->item($classCategoryId) === null) {
            throw new ClassCategoryNotFoundException();
        }

        $classCategories->delete($classCategoryId);

        $this->classCategoryId = $classCategoryId;
    }
}
