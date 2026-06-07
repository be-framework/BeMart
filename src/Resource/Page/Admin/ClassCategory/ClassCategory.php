<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\ClassCategory;

use BEAR\ApiDoc\Annotation\Alps;
use MyVendor\BeMart\Annotation\CsrfProtected;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\ClassCategoryNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\ClassCategoryDeleted;
use MyVendor\BeMart\Be\Final\ClassCategoryUpdated;
use MyVendor\BeMart\Be\Input\DeleteClassCategoryInput;
use MyVendor\BeMart\Be\Input\UpdateClassCategoryInput;
use BEAR\Resource\Annotation\JsonSchema;

use function assert;

/**
 * EC-CUBE doUpdateClassCategory + doDeleteClassCategory — single-row
 * endpoint (Wave 7).
 *
 *   - PUT    → doUpdateClassCategory (admin renames a value —
 *                                     idempotent)
 *   - DELETE → doDeleteClassCategory (admin removes a value —
 *                                     idempotent)
 */
class ClassCategory extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
    ) {
    }

    /**
     * ALPS `doUpdateClassCategory` に対応する PUT 操作。
     * @psalm-taint-source input $classCategoryId
     * @psalm-taint-source input $classCategoryName
     */
    #[Alps('doUpdateClassCategory')]
    #[JsonSchema(schema: 'put-admin-class-category-class-category.json', params: 'put-admin-class-category-class-category.param.json')]
    #[Link(rel: 'goClassCategoryList', href: 'page://self/admin/class-category/class-category-list')]
    #[CsrfProtected]
    public function onPut(
        string $classCategoryId,
        string|null $classCategoryName = null,
    ): static {
        $final = ($this->becoming)(new UpdateClassCategoryInput(
            classCategoryId: $classCategoryId,
            classCategoryName: $classCategoryName,
        ));

        assert($final instanceof ClassCategoryUpdated);

        $this->code = Code::OK;
        $this->body = [
            'classCategoryId' => $final->classCategoryId,
            'classNameId' => $final->classNameId,
            'name' => $final->name,
        ];

        return $this;
    }

    /**
     * ALPS `doUpdateClassCategory` に対応する DELETE 操作。
     * @psalm-taint-source input $classCategoryId
     */
    #[Alps('doUpdateClassCategory')]
    #[JsonSchema(schema: 'delete-admin-class-category-class-category.json', params: 'delete-admin-class-category-class-category.param.json')]
    #[Link(rel: 'goClassCategoryList', href: 'page://self/admin/class-category/class-category-list')]
    #[CsrfProtected]
    public function onDelete(string $classCategoryId): static
    {
        $final = ($this->becoming)(new DeleteClassCategoryInput(classCategoryId: $classCategoryId));

        assert($final instanceof ClassCategoryDeleted);

        $this->code = Code::OK;
        $this->body = ['classCategoryId' => $final->classCategoryId];

        return $this;
    }
}
