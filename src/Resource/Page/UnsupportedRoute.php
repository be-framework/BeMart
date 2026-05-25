<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Annotation\CsrfProtected;

use function str_starts_with;

/** Safe placeholder for template routes that are not backed by a resource yet. */
class UnsupportedRoute extends ResourceObject
{
    public function onGet(string $routeName = ''): static
    {
        $this->code = Code::OK;
        $this->body = [
            'routeName' => $routeName,
            'message' => 'このルートは未実装です。',
        ];

        return $this;
    }

    #[CsrfProtected]
    public function onPost(string $routeName = '', string|null $returnTo = null): static
    {
        $this->code = Code::OK;
        $this->headers['Location'] = $this->safeReturnTo($returnTo);
        $this->body = [
            'routeName' => $routeName,
            'message' => 'この操作は未実装のため何も変更していません。',
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
