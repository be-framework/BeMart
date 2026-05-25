<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\ClassNameNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Entity\ClassCategoryEntity;
use MyVendor\BeMart\Be\Reason\Query\ClassCategoryStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\ClassNameStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Provider\ClassCategoryIdProvider;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Class category created — Final, proof a new variant VALUE was
 * persisted by an admin operation (Wave 7).
 *
 *   CreateClassCategoryInput → ClassCategoryCreated (Direct, admin
 *                                                    AUTHZ)
 *
 * Referential integrity: the referenced classNameId must resolve to an
 * existing axis. A bogus classNameId raises
 * {@see ClassNameNotFoundException} (404) after the admin firewall
 * check but before persistence.
 */
final readonly class ClassCategoryCreated
{
    public string $classCategoryId;
    public string $classNameId;
    public string $name;

    public function __construct(
        #[Input] string $classNameId,
        #[Input] string $classCategoryName,
        #[Inject] AdminSession $adminSession,
        #[Inject] ClassNameStorageInterface $classNames,
        #[Inject] ClassCategoryStorageInterface $classCategories,
        #[Inject] ClassCategoryIdProvider $ids,
    ) {
        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        if ($classNames->item($classNameId) === null) {
            throw new ClassNameNotFoundException();
        }

        $entity = new ClassCategoryEntity(
            classCategoryId: $ids->get(),
            classNameId: $classNameId,
            name: $classCategoryName,
        );

        $classCategories->put($entity);

        $this->classCategoryId = $entity->classCategoryId;
        $this->classNameId = $entity->classNameId;
        $this->name = $entity->name;
    }
}
