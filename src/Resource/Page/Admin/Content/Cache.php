<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\Content;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Annotation\CsrfProtected;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\CacheCleared;
use MyVendor\BeMart\Be\Input\ClearCacheInput;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use BEAR\Resource\Annotation\JsonSchema;

use function assert;

/**
 * EC-CUBE キャッシュ管理 — admin CMS page.
 *
 * Hard ActionRedirect completion: `onPut` drives the Be `doClearCache`
 * transition ({@see ClearCacheInput} → {@see CacheCleared}); the actual
 * cache-directory purge is isolated behind
 * {@see \MyVendor\BeMart\Be\Reason\Service\CacheClearerInterface}. ALPS
 * marks the transition `idempotent` → PUT. `onGet` renders the screen.
 */
class Cache extends ResourceObject
{
    public function __construct(
        private readonly AdminSession $adminSession,
        private readonly BecomingInterface $becoming,
    ) {
    }

    /** ALPS `doClearCache` に対応する GET 操作。 */
    #[Alps('doClearCache')]
    #[JsonSchema(schema: 'get-admin-content-cache.json')]
    #[Link(rel: 'doClearCache', href: 'page://self/admin/content/cache', method: 'put')]
    public function onGet(): static
    {
        if ($this->adminSession->adminId === null) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        $this->code = Code::OK;
        $this->body = [];

        return $this;
    }

    /** Clears the application cache (doClearCache). */
    /** ALPS `doClearCache` に対応する PUT 操作。 */
    #[Alps('doClearCache')]
    #[JsonSchema(schema: 'put-admin-content-cache.json', params: 'put-admin-content-cache.param.json')]
    #[Link(rel: 'goMaintenance', href: 'page://self/admin/content/maintenance')]
    #[CsrfProtected]
    public function onPut(string|null $csrfToken = null): static
    {
        $final = ($this->becoming)(new ClearCacheInput());

        assert($final instanceof CacheCleared);

        $this->code = Code::OK;
        $this->headers['Location'] = '/admin/content/cache';
        $this->body = [
            'transitionId' => 'doClearCache',
            'message' => 'キャッシュを削除しました。',
        ];

        return $this;
    }
}
