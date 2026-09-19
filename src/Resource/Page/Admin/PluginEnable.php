<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin;

use BEAR\ApiDoc\Annotation\Alps;
use Ray\Csrf\Attribute\CsrfToken;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\PluginNotFoundException;
use MyVendor\BeMart\Be\Exception\PluginNotInstalledException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\PluginEnabled;
use MyVendor\BeMart\Be\Input\EnablePluginInput;
use BEAR\Resource\Annotation\JsonSchema;

use function assert;

/**
 * EC-CUBE doEnablePlugin — プラグインを有効化する (Wave 8).
 *
 * Sub-resource of the plugin, parallel to PluginDisable. Lives at
 * `page://self/admin/plugin-enable`. Same `sub-resource of Plugin`
 * pattern as Wave 7's OrderStatus (sub-resource of Order).
 *
 * Failure mapping:
 *   - Invalid CSRF                          → 403
 *   - SemanticVariableException             → 400 (pluginCode format)
 *   - UnauthorizedAdminAccessException      → 403 (no admin session)
 *   - PluginNotFoundException               → 404 (unknown pluginCode)
 *   - PluginNotInstalledException           → 409 (uninstalled row)
 *
 * Idempotency: the Final reports `changed=false` when the plugin was
 * already enabled at the time of the request. ALPS `type=idempotent`
 * — repeats are safe.
 */
class PluginEnable extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
    ) {
    }

    /**
     * ALPS `doEnablePlugin` に対応する POST 操作。
     * @psalm-taint-source input $pluginCode
     */
    #[Alps('doEnablePlugin')]
    #[JsonSchema(schema: 'post-admin-plugin-enable.json', params: 'post-admin-plugin-enable.param.json')]
    #[Link(rel: 'goPluginList', href: 'page://self/admin/plugin-list', method: 'get')]
    #[CsrfToken]
    public function onPost(string $pluginCode): static
    {
        $final = ($this->becoming)(new EnablePluginInput(pluginCode: $pluginCode));

        assert($final instanceof PluginEnabled);

        $this->code = Code::OK;
        $this->body = [
            'pluginCode' => $final->pluginCode,
            'enabled' => $final->enabled,
            'changed' => $final->changed,
        ];

        return $this;
    }
}
