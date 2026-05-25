<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Entity\ClassNameEntity;
use MyVendor\BeMart\Be\Reason\Query\ClassNameStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Provider\ClassNameIdProvider;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Class name created — Final, proof a new variant axis was persisted
 * by an admin operation (Wave 7).
 *
 *   CreateClassNameInput → ClassNameCreated (Direct, admin AUTHZ)
 */
final readonly class ClassNameCreated
{
    public string $classNameId;
    public string $name;

    public function __construct(
        #[Input] string $classNameLabel,
        #[Inject] AdminSession $adminSession,
        #[Inject] ClassNameStorageInterface $classNames,
        #[Inject] ClassNameIdProvider $ids,
    ) {
        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $entity = new ClassNameEntity(
            classNameId: $ids->get(),
            name: $classNameLabel,
        );

        $classNames->put($entity);

        $this->classNameId = $entity->classNameId;
        $this->name = $entity->name;
    }
}
