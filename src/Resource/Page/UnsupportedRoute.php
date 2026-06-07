<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Annotation\CsrfProtected;
use BEAR\Resource\Annotation\JsonSchema;

use function str_starts_with;

/** Safe placeholder for template routes that are not backed by a resource yet. */
class UnsupportedRoute extends ResourceObject
{
    /** ALPS `goUnsupportedRoute` に対応する GET 操作。 */
    #[Alps('goUnsupportedRoute')]
    #[JsonSchema(schema: 'get-unsupported-route.json', params: 'get-unsupported-route.param.json')]
    public function onGet(string $routeName = ''): static
    {
        $this->code = Code::OK;
        $this->body = [
            'routeName' => $routeName,
            'message' => 'このルートは現在利用できません。',
        ];

        return $this;
    }

    /** ALPS `doUnsupportedRoute` に対応する POST 操作。 */
    #[Alps('doUnsupportedRoute')]
    #[JsonSchema(schema: 'post-unsupported-route.json', params: 'post-unsupported-route.param.json')]
    #[CsrfProtected]
    public function onPost(string $routeName = '', string|null $returnTo = null): static
    {
        $this->code = Code::OK;
        $this->headers['Location'] = $this->safeReturnTo($returnTo);
        $this->body = [
            'routeName' => $routeName,
            'message' => 'この操作は現在利用できないため何も変更していません。',
        ];

        return $this;
    }

    private function safeReturnTo(string|null $returnTo): string
    {
        if ($returnTo !== null && str_starts_with($returnTo, '/') && ! str_starts_with($returnTo, '//')) {
            return $returnTo;
        }

        return '/';
    }
}
