<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Reason\Service\ProductCacheInvalidatorInterface;
use MyVendor\BeMart\Be\Exception\ClassCategoryNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Query\ClassCategoryStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
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
    public string $classNameId;

    public function __construct(
        #[Input] string $classCategoryId,
        #[Inject] AdminSession $adminSession,
        #[Inject] ClassCategoryStorageInterface $classCategories,
        #[Inject] ProductCacheInvalidatorInterface $cacheInvalidator,
    ) {
        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $classCategory = $classCategories->item($classCategoryId);
        if ($classCategory === null) {
            throw new ClassCategoryNotFoundException();
        }

        $classCategories->delete($classCategoryId);

        $cacheInvalidator->invalidateCorpus();

        $this->classCategoryId = $classCategoryId;
        $this->classNameId = $classCategory->classNameId;
    }
}
