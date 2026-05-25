<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\Content;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;

/**
 * EC-CUBE キャッシュ管理 — admin CMS thin renderer (Phase 3 HTML).
 *
 * PORT-side note: EC-CUBE's `CacheController` clears the Twig / Symfony
 * cache directories on POST; there is no Be domain entity for it. The
 * `Content/cache.twig` screen is a single "キャッシュ削除" button — the
 * only `form_widget` call is the CSRF `_token` (EC-CUBE-runtime, kept as
 * a render-diff residual). This resource is therefore a THIN HTML
 * RENDERER only — it carries no `be/src/` Becoming chain and no form,
 * authenticating at the resource layer via {@see AdminSession}.
 *
 * FLAGGED: the cache-clear POST action is not modelled (it is an
 * infra/operational action, not a domain mutation); only the GET render
 * is provided.
 */
class Cache extends ResourceObject
{
    public function __construct(
        private readonly AdminSession $adminSession,
    ) {
    }

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
}
