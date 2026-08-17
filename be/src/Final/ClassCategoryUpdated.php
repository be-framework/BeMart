<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Reason\Service\ProductCacheInvalidatorInterface;
use MyVendor\BeMart\Be\Exception\ClassCategoryNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Entity\ClassCategoryEntity;
use MyVendor\BeMart\Be\Reason\Query\ClassCategoryStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Class category updated — Final, proof one variant value was edited
 * in place (Wave 7).
 *
 *   UpdateClassCategoryInput → ClassCategoryUpdated (Direct,
 *                                                    idempotent)
 */
final readonly class ClassCategoryUpdated
{
    public string $classCategoryId;
    public string $classNameId;
    public string $name;

    public function __construct(
        #[Input] string $classCategoryId,
        #[Input] string|null $classCategoryName,
        #[Inject] AdminSession $adminSession,
        #[Inject] ClassCategoryStorageInterface $classCategories,
        #[Inject] ProductCacheInvalidatorInterface $cacheInvalidator,
    ) {
        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $current = $classCategories->item($classCategoryId);
        if ($current === null) {
            throw new ClassCategoryNotFoundException();
        }

        $merged = new ClassCategoryEntity(
            classCategoryId: $current->classCategoryId,
            classNameId: $current->classNameId,
            name: $classCategoryName ?? $current->name,
        );

        $classCategories->put($merged);

        $cacheInvalidator->invalidateCorpus();

        $this->classCategoryId = $merged->classCategoryId;
        $this->classNameId = $merged->classNameId;
        $this->name = $merged->name;
    }
}
