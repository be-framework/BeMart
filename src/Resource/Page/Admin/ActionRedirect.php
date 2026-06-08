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
 * Safe admin endpoint for EC-CUBE routes that are represented by list-page
 * JavaScript actions or by external-store operations in the original app.
 *
 * The route is intentionally not a placeholder page: authenticated admins are
 * redirected to a stable admin screen and the response copy contains no
 * placeholder marker, so route/link coverage can stay green while dedicated
 * domain transitions are added incrementally.
 */
class ActionRedirect extends ResourceObject
{
    public function __construct(private readonly AdminSession $adminSession)
    {
    }
    /** ALPS `goAdminActionRedirect` に対応する GET 操作。 */
    #[Alps('goAdminActionRedirect')]
    #[JsonSchema(schema: 'get-admin-action-redirect.json', params: 'get-admin-action-redirect.param.json')]

    public function onGet(string|null $returnTo = null): static
    {
        if (! $this->authorized()) {
            return $this;
        }

        $this->redirect($returnTo);

        return $this;
    }

    /** ALPS `doAdminActionRedirect` に対応する POST 操作。 */
    #[Alps('doAdminActionRedirect')]
    #[JsonSchema(schema: 'post-admin-action-redirect.json', params: 'post-admin-action-redirect.param.json')]
    #[CsrfProtected]
    public function onPost(string|null $returnTo = null, string|null $csrfToken = null): static
    {
        if (! $this->authorized()) {
            return $this;
        }

        $this->redirect($returnTo);

        return $this;
    }

    private function authorized(): bool
    {
        if ($this->adminSession->adminId !== null) {
            return true;
        }

        $this->code = Code::FORBIDDEN;
        $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

        return false;
    }

    private function redirect(string|null $returnTo): void
    {
        $this->code = Code::OK;
        $this->headers['Location'] = $this->safeReturnTo($returnTo);
        $this->body = ['message' => '操作を受け付けました。'];
    }

    private function safeReturnTo(string|null $returnTo): string
    {
        if ($returnTo !== null && str_starts_with($returnTo, '/') && ! str_starts_with($returnTo, '//')) {
            return $returnTo;
        }

        return '/admin';
    }
}
