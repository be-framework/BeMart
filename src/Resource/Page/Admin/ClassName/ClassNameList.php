<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\ClassName;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\AdminClassNameListFetched;
use MyVendor\BeMart\Be\Final\ClassNameCreated;
use MyVendor\BeMart\Be\Input\CreateClassNameInput;
use MyVendor\BeMart\Be\Input\GetAdminClassNameListInput;
use MyVendor\BeMart\Be\Reason\Service\CsrfTokenInterface;

use function assert;
use function sprintf;
use function urlencode;

/**
 * EC-CUBE goClassNameList + doCreateClassName — collection endpoint
 * (Wave 7).
 *
 *   - GET  → goClassNameList   (admin lists axes — safe read)
 *   - POST → doCreateClassName (admin adds a new axis)
 *
 * Single-row affordances (`doUpdateClassName`, `doDeleteClassName`)
 * live at `page://self/admin/class-name/class-name`. There is no
 * dedicated `goClassName` (admin reads the list directly).
 */
class ClassNameList extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
        private readonly CsrfTokenInterface $csrf,
    ) {
    }

    #[Link(rel: 'doCreateClassName', href: 'page://self/admin/class-name/class-name-list', method: 'post')]
    #[Link(rel: 'doUpdateClassName', href: 'page://self/admin/class-name/class-name', method: 'put')]
    #[Link(rel: 'doDeleteClassName', href: 'page://self/admin/class-name/class-name', method: 'delete')]
    public function onGet(): static
    {
        try {
            $final = ($this->becoming)(new GetAdminClassNameListInput());
        } catch (UnauthorizedAdminAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        assert($final instanceof AdminClassNameListFetched);

        $this->code = Code::OK;
        $this->body = [
            'count' => $final->count,
            'classNames' => $final->classNames,
        ];

        return $this;
    }

    /**
     * @psalm-taint-source input $classNameLabel
     * @psalm-taint-source input $csrfToken
     */
    #[Link(rel: 'goClassNameList', href: 'page://self/admin/class-name/class-name-list')]
    public function onPost(string $classNameLabel, string|null $csrfToken = null): static
    {
        if (! $this->csrf->isValid($csrfToken)) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'Invalid or missing CSRF token.'];

            return $this;
        }

        try {
            $final = ($this->becoming)(new CreateClassNameInput(classNameLabel: $classNameLabel));
        } catch (SemanticVariableException $e) {
            $this->code = Code::BAD_REQUEST;
            $this->body = ['message' => $e->getErrors()->getMessages('ja')[0] ?? 'Invalid input.'];

            return $this;
        } catch (UnauthorizedAdminAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        assert($final instanceof ClassNameCreated);

        $this->code = Code::CREATED;
        $this->headers['Location'] = sprintf('/admin/class-name/class-name?classNameId=%s', urlencode($final->classNameId));
        $this->body = [
            'classNameId' => $final->classNameId,
            'name' => $final->name,
        ];

        return $this;
    }
}
