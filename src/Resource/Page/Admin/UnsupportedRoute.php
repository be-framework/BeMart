<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Annotation\CsrfProtected;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use BEAR\Resource\Annotation\JsonSchema;

use function str_starts_with;

/**
 * Safe placeholder for EC-CUBE admin routes that are referenced by ported
 * templates but not yet backed by a dedicated Be transition.
 */
class UnsupportedRoute extends ResourceObject
{
    public function __construct(
        private readonly AdminSession $adminSession,
    ) {
    }
    /** ALPS `goAdminUnsupportedRoute` に対応する GET 操作。 */
    #[Alps('goAdminUnsupportedRoute')]
    #[JsonSchema(schema: 'get-admin-unsupported-route.json', params: 'get-admin-unsupported-route.param.json')]

    public function onGet(string $routeName = ''): static
    {
        if ($this->adminSession->adminId === null) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        $this->code = Code::OK;
        $this->body = [
            'routeName' => $routeName,
            'message' => 'この管理画面ルートは現在利用できません。',
        ];

        return $this;
    }

    /** ALPS `doAdminUnsupportedRoute` に対応する POST 操作。 */
    #[Alps('doAdminUnsupportedRoute')]
    #[JsonSchema(schema: 'post-admin-unsupported-route.json', params: 'post-admin-unsupported-route.param.json')]
    #[CsrfProtected]
    public function onPost(string $routeName = '', string|null $returnTo = null): static
    {
        if ($this->adminSession->adminId === null) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        $this->code = Code::OK;
        $this->headers['Location'] = $this->safeReturnTo($returnTo);
        $this->body = [
            'routeName' => $routeName,
            'message' => 'この管理画面操作は現在利用できないため何も変更していません。',
        ];

        return $this;
    }

    private function safeReturnTo(string|null $returnTo): string
    {
        if ($returnTo !== null && str_starts_with($returnTo, '/') && ! str_starts_with($returnTo, '//')) {
            return $returnTo;
        }

        return '/admin';
    }
}
