<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Ray\Csrf\Attribute\CsrfToken;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Support\Resource\MutationResponseInterface;
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
        private readonly MutationResponseInterface $mutationResponse,
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
    #[CsrfToken]
    public function onPost(string $routeName = '', string|null $returnTo = null): static
    {
        if ($this->adminSession->adminId === null) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        // Post/Redirect/Get: a browser follows the safe Location with 303;
        // JSON/Resource clients keep the no-op body with 200 OK.
        ($this->mutationResponse)($this, Code::OK, $this->safeReturnTo($returnTo));
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
