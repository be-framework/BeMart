<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin;

use MyVendor\BeMart\Annotation\CsrfProtected;
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
     * @psalm-taint-source input $pluginCode
     */
    #[Link(rel: 'goPluginList', href: 'page://self/admin/plugin-list', method: 'get')]
    #[CsrfProtected]
    public function onPost(string $pluginCode): static
    {
        try {
            $final = ($this->becoming)(new EnablePluginInput(pluginCode: $pluginCode));
        } catch (SemanticVariableException $e) {
            $this->code = Code::BAD_REQUEST;
            $this->body = ['message' => $e->getErrors()->getMessages('ja')[0] ?? 'Invalid input.'];

            return $this;
        } catch (UnauthorizedAdminAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        } catch (PluginNotFoundException) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => 'プラグインが見つかりませんでした。'];

            return $this;
        } catch (PluginNotInstalledException) {
            // BEAR\Resource\Code lacks CONFLICT; use the integer literal
            // (same convention as Pilot 4's Entry resource).
            $this->code = 409;
            $this->body = ['message' => 'プラグインがインストールされていません。'];

            return $this;
        }

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
