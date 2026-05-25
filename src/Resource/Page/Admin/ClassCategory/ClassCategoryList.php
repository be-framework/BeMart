<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\ClassCategory;

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
use MyVendor\BeMart\Be\Reason\Service\CsrfTokenInterface;

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
        private readonly CsrfTokenInterface $csrf,
    ) {
    }

    /**
     * @psalm-taint-source input $classNameId
     */
    #[Link(rel: 'doCreateClassCategory', href: 'page://self/admin/class-category/class-category-list', method: 'post')]
    #[Link(rel: 'doUpdateClassCategory', href: 'page://self/admin/class-category/class-category', method: 'put')]
    #[Link(rel: 'doDeleteClassCategory', href: 'page://self/admin/class-category/class-category', method: 'delete')]
    public function onGet(string|null $classNameId = null): static
    {
        try {
            $final = ($this->becoming)(new GetAdminClassCategoryListInput(classNameId: $classNameId));
        } catch (UnauthorizedAdminAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        assert($final instanceof AdminClassCategoryListFetched);

        $this->code = Code::OK;
        $this->body = [
            'classNameId' => $final->classNameId,
            'count' => $final->count,
            'classCategories' => $final->classCategories,
        ];

        return $this;
    }

    /**
     * @psalm-taint-source input $classNameId
     * @psalm-taint-source input $classCategoryName
     * @psalm-taint-source input $csrfToken
     */
    #[Link(rel: 'goClassCategoryList', href: 'page://self/admin/class-category/class-category-list')]
    public function onPost(
        string $classNameId,
        string $classCategoryName,
        string|null $csrfToken = null,
    ): static {
        if (! $this->csrf->isValid($csrfToken)) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'Invalid or missing CSRF token.'];

            return $this;
        }

        try {
            $final = ($this->becoming)(new CreateClassCategoryInput(
                classNameId: $classNameId,
                classCategoryName: $classCategoryName,
            ));
        } catch (SemanticVariableException $e) {
            $this->code = Code::BAD_REQUEST;
            $this->body = ['message' => $e->getErrors()->getMessages('ja')[0] ?? 'Invalid input.'];

            return $this;
        } catch (UnauthorizedAdminAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        } catch (ClassNameNotFoundException) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => '指定された規格名は見つかりませんでした。'];

            return $this;
        }

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
