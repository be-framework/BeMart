<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\Content;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;

/**
 * EC-CUBE メンテナンス管理 — admin CMS thin renderer (Phase 3 HTML).
 *
 * PORT-side note: EC-CUBE's `MaintenanceController` toggles the
 * maintenance-mode marker file; there is no Be domain entity for it. The
 * `Content/maintenance.twig` screen is a single有効/無効 toggle button —
 * the only `form_widget` call is the CSRF `_token` (EC-CUBE-runtime,
 * kept as a render-diff residual). This resource is a THIN HTML RENDERER
 * only — it carries no `be/src/` Becoming chain, authenticating at the
 * resource layer via {@see AdminSessionInterface}. `body['isMaintenance']`
 * drives which toggle button the template shows; it defaults to false
 * (maintenance off — the fresh-install state).
 *
 * FLAGGED: the maintenance-toggle POST action and the persisted
 * maintenance state are not modelled (operational, not a domain
 * mutation); only the GET render of the off-state is provided.
 */
class Maintenance extends ResourceObject
{
    public function __construct(
        private readonly AdminSessionInterface $adminSession,
    ) {
    }

    public function onGet(): static
    {
        if ($this->adminSession->adminId() === null) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        $this->code = Code::OK;
        $this->body = ['isMaintenance' => false];

        return $this;
    }
}
