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
use MyVendor\BeMart\Be\Final\AdminCategoryListFetched;
use MyVendor\BeMart\Be\Input\GetAdminCategoryInput;
use MyVendor\BeMart\Be\Input\GetAdminCategoryListInput;
use MyVendor\BeMart\Form\AdminCategoryForm;
use Ray\WebFormModule\FormFactory;

use function assert;

/**
 * EC-CUBE カテゴリ登録 / カテゴリ編集 — Product Tier-2
 * (`admin/Product/category.twig`, the category tree-list + inline
 * add/edit screen).
 *
 *   GET /admin/category/edit                 → tree list + blank "new" form
 *   GET /admin/category/edit?categoryId=…    → tree list + form pre-filled
 *
 * Thin GET renderer. The sibling JSON resources
 * {@see \MyVendor\BeMart\Resource\Page\Admin\Category\CategoryList}
 * (collection + create) and {@see \MyVendor\BeMart\Resource\Page\Admin\Category\Category}
 * (update / delete) carry the writes; this resource is the HTML editor
 * shell — it renders the category tree alongside the add/edit form. An
 * empty `$categoryId` renders the blank "新規カテゴリ" form (the
 * render-smoke test exercises this with empty JSON-backed fake storage); a known
 * categoryId pre-fills; an unknown categoryId is 404.
 *
 * AUTHZ delegates to the Be Finals, which raise
 * {@see UnauthorizedAdminAccessException} for a non-admin firewall —
 * surfaced as 403.
 */
class Edit extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
        private readonly FormFactory $formFactory,
    ) {
    }

    /**
     * @psalm-taint-source input $categoryId
     */
    #[Link(rel: 'doCreateCategory', href: 'page://self/admin/category/category-list', method: 'post')]
    #[Link(rel: 'doUpdateCategory', href: 'page://self/admin/category/category', method: 'put')]
    #[Link(rel: 'goCategoryList', href: 'page://self/admin/category/category-list')]
    public function onGet(string $categoryId = ''): static
    {
        try {
            $listFinal = ($this->becoming)(new GetAdminCategoryListInput());
        } catch (UnauthorizedAdminAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        assert($listFinal instanceof AdminCategoryListFetched);

        $form = $this->formFactory->newInstance(AdminCategoryForm::class);
        assert($form instanceof AdminCategoryForm);

        if ($categoryId === '') {
            $form->fillValues(['name' => '', 'parent_id' => '', 'sort_no' => '']);

            $this->code = Code::OK;
            $this->body = [
                'form' => $form,
                'categoryId' => '',
                'category' => null,
                'categories' => $listFinal->categories,
                'count' => $listFinal->count,
            ];

            return $this;
        }

        try {
            $final = ($this->becoming)(new GetAdminCategoryInput(categoryId: $categoryId));
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

        assert($final instanceof AdminCategoryFetched);

        $form->fillValues([
            'name' => $final->categoryName,
            'parent_id' => $final->parentId ?? '',
            'sort_no' => (string) $final->sortNo,
        ]);

        $this->code = Code::OK;
        $this->body = [
            'form' => $form,
            'categoryId' => $final->categoryId,
            'category' => [
                'categoryId' => $final->categoryId,
                'categoryName' => $final->categoryName,
                'parentId' => $final->parentId,
                'sortNo' => $final->sortNo,
            ],
            'categories' => $listFinal->categories,
            'count' => $listFinal->count,
        ];

        return $this;
    }
}
