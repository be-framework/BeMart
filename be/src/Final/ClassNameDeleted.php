<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\ClassNameNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Query\ClassNameStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Class name deleted — Final, proof one axis was removed (Wave 7).
 *
 *   DeleteClassNameInput → ClassNameDeleted (Direct, idempotent)
 *
 * ALPS doc note: real EC-CUBE refuses deletion when child ClassCategory
 * rows or referencing ProductClass rows exist. The in-memory store
 * does NOT enforce that referential guard in this first iteration —
 * the deletion is unconditional once AUTHZ + existence pass. Phase 2
 * will add the dependency check once a real consumer asks for it.
 */
final readonly class ClassNameDeleted
{
    public string $classNameId;

    public function __construct(
        #[Input] string $classNameId,
        #[Inject] AdminSession $adminSession,
        #[Inject] ClassNameStorageInterface $classNames,
    ) {
        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        if ($classNames->item($classNameId) === null) {
            throw new ClassNameNotFoundException();
        }

        $classNames->delete($classNameId);

        $this->classNameId = $classNameId;
    }
}
