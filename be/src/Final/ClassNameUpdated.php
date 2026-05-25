<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\ClassNameNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Entity\ClassNameEntity;
use MyVendor\BeMart\Be\Reason\Query\ClassNameStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Class name updated — Final, proof one axis row was edited in place
 * (Wave 7).
 *
 *   UpdateClassNameInput → ClassNameUpdated (Direct, idempotent)
 *
 * AUTHZ ladder:
 *   1. No admin session   → UnauthorizedAdminAccessException (403)
 *   2. Unknown id         → ClassNameNotFoundException       (404)
 */
final readonly class ClassNameUpdated
{
    public string $classNameId;
    public string $name;

    public function __construct(
        #[Input] string $classNameId,
        #[Input] string|null $classNameLabel,
        #[Inject] AdminSessionInterface $adminSession,
        #[Inject] ClassNameStorageInterface $classNames,
    ) {
        if ($adminSession->adminId() === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $current = $classNames->getById($classNameId);
        if ($current === null) {
            throw new ClassNameNotFoundException();
        }

        $merged = new ClassNameEntity(
            classNameId: $current->classNameId,
            name: $classNameLabel ?? $current->name,
        );

        $classNames->put($merged);

        $this->classNameId = $merged->classNameId;
        $this->name = $merged->name;
    }
}
