<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\Category;

use MyVendor\BeMart\Annotation\CsrfProtected;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\CategoryNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\AdminCategoryListFetched;
use MyVendor\BeMart\Be\Final\CategoryCreated;
use MyVendor\BeMart\Be\Input\CreateCategoryInput;
use MyVendor\BeMart\Be\Input\GetAdminCategoryListInput;

use function assert;
use function sprintf;
use function urlencode;

/**
 * EC-CUBE goCategoryList + doCreateCategory — collection endpoint
 * (Wave 7).
 *
 *   - GET  → goCategoryList    (admin lists categories — safe read)
 *   - POST → doCreateCategory  (admin adds a new category)
 *
 * Single-row affordances (`goCategory`, `doUpdateCategory`,
 * `doDeleteCategory`) live at `page://self/admin/category/category`.
 * CSV affordances live at `page://self/admin/category/csv`.
 *
 * Failure mapping (collapsed admin AUTHZ + CSRF + format):
 *   - SemanticVariableException             → 400 (parameter format)
 *   - UnauthorizedAdminAccessException      → 403 (no admin session)
 *   - CategoryNotFoundException (parentId)  → 404 (referenced parent
 *                                                  does not exist)
 *   - CSRF mismatch (POST)                  → 403
 */
class CategoryList extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
    ) {
    }

    #[Link(rel: 'doCreateCategory', href: 'page://self/admin/category/category-list', method: 'post')]
    #[Link(rel: 'goCategory', href: 'page://self/admin/category/category', method: 'get')]
    #[Link(rel: 'doUpdateCategory', href: 'page://self/admin/category/category', method: 'put')]
    #[Link(rel: 'doDeleteCategory', href: 'page://self/admin/category/category', method: 'delete')]
    #[Link(rel: 'doImportCategoryCsv', href: 'page://self/admin/category/csv', method: 'post')]
    #[Link(rel: 'goExportCategory', href: 'page://self/admin/category/csv', method: 'get')]
    public function onGet(): static
    {
        try {
            $final = ($this->becoming)(new GetAdminCategoryListInput());
        } catch (UnauthorizedAdminAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        assert($final instanceof AdminCategoryListFetched);

        $this->code = Code::OK;
        $this->body = [
            'count' => $final->count,
            'categories' => $final->categories,
        ];

        return $this;
    }

    /**
     * @psalm-taint-source input $categoryName
     * @psalm-taint-source input $sortNo
     * @psalm-taint-source input $parentId
     */
    #[Link(rel: 'goCategoryList', href: 'page://self/admin/category/category-list')]
    #[CsrfProtected]
    public function onPost(
        string $categoryName,
        int $sortNo,
        string|null $parentId = null,
    ): static {
        try {
            $final = ($this->becoming)(new CreateCategoryInput(
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
            $this->body = ['message' => '指定された親カテゴリは存在しません。'];

            return $this;
        }

        assert($final instanceof CategoryCreated);

        $this->code = Code::CREATED;
        $this->headers['Location'] = sprintf('/admin/category/category?categoryId=%s', urlencode($final->categoryId));
        $this->body = [
            'categoryId' => $final->categoryId,
            'categoryName' => $final->categoryName,
            'parentId' => $final->parentId,
            'sortNo' => $final->sortNo,
        ];

        return $this;
    }
}
