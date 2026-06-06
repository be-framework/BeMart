<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\ClassName;

use MyVendor\BeMart\Annotation\CsrfProtected;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\ClassNameNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\ClassNameDeleted;
use MyVendor\BeMart\Be\Final\ClassNameUpdated;
use MyVendor\BeMart\Be\Input\DeleteClassNameInput;
use MyVendor\BeMart\Be\Input\UpdateClassNameInput;

use function assert;

/**
 * EC-CUBE doUpdateClassName + doDeleteClassName — single-row endpoint
 * (Wave 7).
 *
 *   - PUT    → doUpdateClassName (admin renames an axis — idempotent)
 *   - DELETE → doDeleteClassName (admin removes an axis — idempotent)
 */
class ClassName extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
    ) {
    }

    /**
     * @psalm-taint-source input $classNameId
     * @psalm-taint-source input $classNameLabel
     */
    #[Link(rel: 'goClassNameList', href: 'page://self/admin/class-name/class-name-list')]
    #[CsrfProtected]
    public function onPut(
        string $classNameId,
        string|null $classNameLabel = null,
    ): static {
        $final = ($this->becoming)(new UpdateClassNameInput(
            classNameId: $classNameId,
            classNameLabel: $classNameLabel,
        ));

        assert($final instanceof ClassNameUpdated);

        $this->code = Code::OK;
        $this->body = [
            'classNameId' => $final->classNameId,
            'name' => $final->name,
        ];

        return $this;
    }

    /**
     * @psalm-taint-source input $classNameId
     */
    #[Link(rel: 'goClassNameList', href: 'page://self/admin/class-name/class-name-list')]
    #[CsrfProtected]
    public function onDelete(string $classNameId): static
    {
        $final = ($this->becoming)(new DeleteClassNameInput(classNameId: $classNameId));

        assert($final instanceof ClassNameDeleted);

        $this->code = Code::OK;
        $this->body = ['classNameId' => $final->classNameId];

        return $this;
    }
}
