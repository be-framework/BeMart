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
use MyVendor\BeMart\Be\Exception\ClassNameNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\AdminClassCategoryListFetched;
use MyVendor\BeMart\Be\Final\ClassCategoryCreated;
use MyVendor\BeMart\Be\Input\CreateClassCategoryInput;
use MyVendor\BeMart\Be\Input\GetAdminClassCategoryListInput;
use MyVendor\BeMart\Form\AdminClassCategoryForm;
use Ray\WebFormModule\FormFactory;
use BEAR\Resource\Annotation\JsonSchema;

use function assert;
use function sprintf;
use function urlencode;

/**
 * EC-CUBE goClassCategoryList + doCreateClassCategory — collection
 * endpoint (Wave 7).
 *
 *   - GET  → goClassCategoryList   (admin lists VALUES — safe read)
 *   - POST → doCreateClassCategory (admin adds a new value under one
 *                                   axis)
 *
 * Optional `?classNameId=` query parameter narrows the GET list to one
 * axis; omit it for the unscoped grid view.
 */
class ClassCategoryList extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
        private readonly FormFactory $formFactory,
    ) {
    }

    /**
     * ALPS `goClassCategoryList` に対応する GET 操作。
     * @psalm-taint-source input $classNameId
     */
    #[Alps('goClassCategoryList')]
    #[JsonSchema(schema: 'get-admin-class-category-class-category-list.json', params: 'get-admin-class-category-class-category-list.param.json')]
    #[Link(rel: 'doCreateClassCategory', href: 'page://self/admin/class-category/class-category-list', method: 'post')]
    #[Link(rel: 'doUpdateClassCategory', href: 'page://self/admin/class-category/class-category', method: 'put')]
    #[Link(rel: 'doDeleteClassCategory', href: 'page://self/admin/class-category/class-category', method: 'delete')]
    public function onGet(string|null $classNameId = null): static
    {
        $final = ($this->becoming)(new GetAdminClassCategoryListInput(classNameId: $classNameId));

        assert($final instanceof AdminClassCategoryListFetched);

        $this->code = Code::OK;
        $this->body = [
            'classNameId' => $final->classNameId,
            'count' => $final->count,
            'classCategories' => $final->classCategories,
        ];
        // Phase 3: an empty AdminClassCategoryForm for the HTML list page
        // to render the inline-create inputs via `{{ form.input(...) }}`.
        // JSON contexts ignore `body.form`.
        $this->body['form'] = $this->formFactory->newInstance(AdminClassCategoryForm::class);

        return $this;
    }

    /**
     * ALPS `doCreateClassCategory` に対応する POST 操作。
     * @psalm-taint-source input $classNameId
     * @psalm-taint-source input $classCategoryName
     */
    #[Alps('doCreateClassCategory')]
    #[JsonSchema(schema: 'post-admin-class-category-class-category-list.json', params: 'post-admin-class-category-class-category-list.param.json')]
    #[Link(rel: 'goClassCategoryList', href: 'page://self/admin/class-category/class-category-list')]
    #[CsrfProtected]
    public function onPost(
        string $classNameId,
        string $classCategoryName,
            string|null $csrfToken = null,
    ): static {
        $final = ($this->becoming)(new CreateClassCategoryInput(
            classNameId: $classNameId,
            classCategoryName: $classCategoryName,
        ));

        assert($final instanceof ClassCategoryCreated);

        $this->code = Code::CREATED;
        $this->headers['Location'] = sprintf(
            '/admin/class-category/class-category?classCategoryId=%s',
            urlencode($final->classCategoryId),
        );
        $this->body = [
            'classCategoryId' => $final->classCategoryId,
            'classNameId' => $final->classNameId,
            'name' => $final->name,
        ];

        return $this;
    }
}
