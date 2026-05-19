<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\Category;

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
use MyVendor\BeMart\Be\Reason\Service\CsrfTokenInterface;

use function assert;

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
        private readonly CsrfTokenInterface $csrf,
    ) {
    }

    /**
     * @psalm-taint-source input $categoryId
     */
    #[Link(rel: 'goCategoryList', href: 'page://self/admin/category/category-list')]
    public function onGet(string $categoryId): static
    {
        try {
            $final = ($this->becoming)(new GetAdminCategoryInput(categoryId: $categoryId));
        } catch (UnauthorizedAdminAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        } catch (CategoryNotFoundException) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => '指定されたカテゴリは見つかりませんでした。'];

            return $this;
        }

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
     * @psalm-taint-source input $categoryId
     * @psalm-taint-source input $categoryName
     * @psalm-taint-source input $sortNo
     * @psalm-taint-source input $parentId
     * @psalm-taint-source input $csrfToken
     */
    #[Link(rel: 'goCategory', href: 'page://self/admin/category/category')]
    public function onPut(
        string $categoryId,
        string|null $categoryName = null,
        int|null $sortNo = null,
        string|null $parentId = null,
        string|null $csrfToken = null,
    ): static {
        if (! $this->csrf->isValid($csrfToken)) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'Invalid or missing CSRF token.'];

            return $this;
        }

        try {
            $final = ($this->becoming)(new UpdateCategoryInput(
                categoryId: $categoryId,
                categoryName: $categoryName,
                sortNo: $sortNo,
                parentId: $parentId,
            ));
        } catch (SemanticVariableException $e) {
            $this->code = Code::BAD_REQUEST;
            $this->body = ['message' => $e->getErrors()->getMessages('ja')[0] ?? 'Invalid input.'];

            return $this;
        } catch (UnauthorizedAdminAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        } catch (CategoryNotFoundException) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => '指定されたカテゴリは見つかりませんでした。'];

            return $this;
        }

        assert($final instanceof CategoryUpdated);

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
     * @psalm-taint-source input $categoryId
     * @psalm-taint-source input $csrfToken
     */
    #[Link(rel: 'goCategoryList', href: 'page://self/admin/category/category-list')]
    public function onDelete(string $categoryId, string|null $csrfToken = null): static
    {
        if (! $this->csrf->isValid($csrfToken)) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'Invalid or missing CSRF token.'];

            return $this;
        }

        try {
            $final = ($this->becoming)(new DeleteCategoryInput(categoryId: $categoryId));
        } catch (UnauthorizedAdminAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        } catch (CategoryNotFoundException) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => '指定されたカテゴリは見つかりませんでした。'];

            return $this;
        }

        assert($final instanceof CategoryDeleted);

        $this->code = Code::OK;
        $this->body = [
            'categoryId' => $final->categoryId,
        ];

        return $this;
    }
}
