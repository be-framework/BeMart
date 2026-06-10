<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\Category;

use BEAR\ApiDoc\Annotation\Alps;
use MyVendor\BeMart\Annotation\CsrfProtected;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\CategoryNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\AdminCategoryFetched;
use MyVendor\BeMart\Be\Final\CategoryDeleted;
use MyVendor\BeMart\Be\Final\CategoryUpdated;
use MyVendor\BeMart\Be\Input\DeleteCategoryInput;
use MyVendor\BeMart\Be\Input\GetAdminCategoryInput;
use MyVendor\BeMart\Be\Input\UpdateCategoryInput;
use BEAR\Resource\Annotation\JsonSchema;

use function assert;
use function getenv;
use function sprintf;
use function str_contains;
use function urlencode;

/**
 * EC-CUBE goCategory + doUpdateCategory + doDeleteCategory —
 * single-row endpoint (Wave 7).
 *
 *   - GET    → goCategory       (admin views one)
 *   - PUT    → doUpdateCategory (admin edits in place — idempotent)
 *   - DELETE → doDeleteCategory (admin removes — idempotent)
 *
 * `categoryId` is the lookup key. The Be Finals enforce the admin
 * AUTHZ ladder; this resource maps exceptions to HTTP codes.
 */
class Category extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
    ) {
    }

    /**
     * ALPS `goCategory` に対応する GET 操作。
     * @psalm-taint-source input $categoryId
     */
    #[Alps('goCategory')]
    #[JsonSchema(schema: 'get-admin-category-category.json', params: 'get-admin-category-category.param.json')]
    #[Link(rel: 'goCategoryList', href: 'page://self/admin/category/category-list')]
    #[Link(rel: 'doUpdateCategory', href: 'page://self/admin/category/category', method: 'put')]
    #[Link(rel: 'doDeleteCategory', href: 'page://self/admin/category/category', method: 'delete')]
    public function onGet(string $categoryId): static
    {
        $final = ($this->becoming)(new GetAdminCategoryInput(categoryId: $categoryId));

        assert($final instanceof AdminCategoryFetched);

        $this->code = Code::OK;
        $this->body = [
            'categoryId' => $final->categoryId,
            'categoryName' => $final->categoryName,
            'parentId' => $final->parentId,
            'sortNo' => $final->sortNo,
        ];

        return $this;
    }

    /**
     * ALPS `doUpdateCategory` に対応する PUT 操作。
     * @psalm-taint-source input $categoryId
     * @psalm-taint-source input $categoryName
     * @psalm-taint-source input $sortNo
     * @psalm-taint-source input $parentId
     */
    #[Alps('doUpdateCategory')]
    #[JsonSchema(schema: 'put-admin-category-category.json', params: 'put-admin-category-category.param.json')]
    #[Link(rel: 'goCategory', href: 'page://self/admin/category/category')]
    #[CsrfProtected]
    public function onPut(
        string $categoryId,
        string|null $categoryName = null,
        int|null $sortNo = null,
        string|null $parentId = null,
    ): static {
        $final = ($this->becoming)(new UpdateCategoryInput(
            categoryId: $categoryId,
            categoryName: $categoryName,
            sortNo: $sortNo,
            parentId: $parentId,
        ));

        assert($final instanceof CategoryUpdated);

        $this->code = str_contains((string) getenv('APP_CONTEXT'), 'html') ? Code::SEE_OTHER : Code::OK;
        $this->headers['Location'] = sprintf('/admin/category/category?categoryId=%s', urlencode($final->categoryId));
        $this->body = [
            'categoryId' => $final->categoryId,
            'categoryName' => $final->categoryName,
            'parentId' => $final->parentId,
            'sortNo' => $final->sortNo,
        ];

        return $this;
    }

    /**
     * ALPS `doDeleteCategory` に対応する DELETE 操作。
     * @psalm-taint-source input $categoryId
     */
    #[Alps('doDeleteCategory')]
    #[JsonSchema(schema: 'delete-admin-category-category.json', params: 'delete-admin-category-category.param.json')]
    #[Link(rel: 'goCategoryList', href: 'page://self/admin/category/category-list')]
    #[CsrfProtected]
    public function onDelete(string $categoryId): static
    {
        $final = ($this->becoming)(new DeleteCategoryInput(categoryId: $categoryId));

        assert($final instanceof CategoryDeleted);

        $this->code = str_contains((string) getenv('APP_CONTEXT'), 'html') ? Code::SEE_OTHER : Code::OK;
        $this->headers['Location'] = '/admin/category/category-list';
        $this->body = [
            'categoryId' => $final->categoryId,
        ];

        return $this;
    }
}
