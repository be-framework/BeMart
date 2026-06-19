<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\Content;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Ray\Csrf\Attribute\CsrfToken;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\MaintenanceToggled;
use MyVendor\BeMart\Be\Input\ToggleMaintenanceInput;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use Ray\Csrf\CsrfTokenInterface;
use MyVendor\BeMart\Be\Reason\Service\MaintenanceModeInterface;
use BEAR\Resource\Annotation\JsonSchema;

use function assert;

/**
 * EC-CUBE メンテナンス管理 — admin CMS page.
 *
 * PORT-side note: EC-CUBE's `MaintenanceController` toggles the
 * maintenance-mode marker file; there is no long-lived business entity for
 * it. This resource models the admin affordance as an explicit
 * `doToggleMaintenance` transition and persists the operational marker
 * through {@see MaintenanceModeInterface}. `body['isMaintenance']` drives
 * which 有効/無効 button the template shows.
 */
class Maintenance extends ResourceObject
{
    public function __construct(
        private readonly AdminSession $adminSession,
        private readonly BecomingInterface $becoming,
        private readonly MaintenanceModeInterface $maintenance,
        private readonly CsrfTokenInterface $csrf,
    ) {
    }

    /** ALPS `goMaintenance` に対応する GET 操作。 */
    #[Alps('goMaintenance')]
    #[JsonSchema(schema: 'get-admin-content-maintenance.json')]
    #[Link(rel: 'doToggleMaintenance', href: 'page://self/admin/content/maintenance', method: 'put')]
    public function onGet(): static
    {
        if ($this->adminSession->adminId === null) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        $this->code = Code::OK;
        $this->body = [
            'isMaintenance' => $this->maintenance->isEnabled(),
            'csrfToken' => $this->csrf->issue(),
        ];

        return $this;
    }

    /**
     * Toggles maintenance mode to an explicit state (doToggleMaintenance).
     * ALPS marks it `idempotent` → PUT.
     *
     * @psalm-taint-source input $enabled
     */
    #[Alps('doToggleMaintenance')]
    #[JsonSchema(schema: 'put-admin-content-maintenance.json', params: 'put-admin-content-maintenance.param.json')]
    #[Link(rel: 'goSystemInfo', href: 'page://self/admin/system')]
    #[CsrfToken]
    public function onPut(bool $enabled, string|null $mode = null): static
    {
        $final = ($this->becoming)(new ToggleMaintenanceInput(enabled: $enabled));

        assert($final instanceof MaintenanceToggled);

        $this->code = $mode === 'content_operation_form' ? Code::SEE_OTHER : Code::OK;
        $this->headers['Location'] = '/admin/content/maintenance';
        $this->body = [
            'transitionId' => 'doToggleMaintenance',
            'isMaintenance' => $final->enabled,
            'message' => $final->enabled ? 'メンテナンスモードを有効にしました。' : 'メンテナンスモードを無効にしました。',
        ];

        return $this;
    }
}
