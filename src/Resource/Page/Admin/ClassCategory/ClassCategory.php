<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\ClassCategory;

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
use MyVendor\BeMart\Be\Reason\Service\CsrfTokenInterface;

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
        private readonly CsrfTokenInterface $csrf,
    ) {
    }

    /**
     * @psalm-taint-source input $classCategoryId
     * @psalm-taint-source input $classCategoryName
     * @psalm-taint-source input $csrfToken
     */
    #[Link(rel: 'goClassCategoryList', href: 'page://self/admin/class-category/class-category-list')]
    public function onPut(
        string $classCategoryId,
        string|null $classCategoryName = null,
        string|null $csrfToken = null,
    ): static {
        if (! $this->csrf->isValid($csrfToken)) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'Invalid or missing CSRF token.'];

            return $this;
        }

        try {
            $final = ($this->becoming)(new UpdateClassCategoryInput(
                classCategoryId: $classCategoryId,
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
        } catch (ClassCategoryNotFoundException) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => '指定された規格分類は見つかりませんでした。'];

            return $this;
        }

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
     * @psalm-taint-source input $classCategoryId
     * @psalm-taint-source input $csrfToken
     */
    #[Link(rel: 'goClassCategoryList', href: 'page://self/admin/class-category/class-category-list')]
    public function onDelete(string $classCategoryId, string|null $csrfToken = null): static
    {
        if (! $this->csrf->isValid($csrfToken)) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'Invalid or missing CSRF token.'];

            return $this;
        }

        try {
            $final = ($this->becoming)(new DeleteClassCategoryInput(classCategoryId: $classCategoryId));
        } catch (UnauthorizedAdminAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        } catch (ClassCategoryNotFoundException) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => '指定された規格分類は見つかりませんでした。'];

            return $this;
        }

        assert($final instanceof ClassCategoryDeleted);

        $this->code = Code::OK;
        $this->body = ['classCategoryId' => $final->classCategoryId];

        return $this;
    }
}
