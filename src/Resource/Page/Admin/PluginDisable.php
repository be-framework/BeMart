<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin;

use BEAR\ApiDoc\Annotation\Alps;
use MyVendor\BeMart\Annotation\CsrfProtected;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\PluginNotFoundException;
use MyVendor\BeMart\Be\Exception\PluginNotInstalledException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\PluginDisabled;
use MyVendor\BeMart\Be\Input\DisablePluginInput;
use BEAR\Resource\Annotation\JsonSchema;

use function assert;

/**
 * EC-CUBE doDisablePlugin — プラグインを無効化する (Wave 8).
 *
 * Sub-resource mirror of {@see PluginEnable}. Same failure ladder,
 * same idempotency convention.
 */
class PluginDisable extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
    ) {
    }

    /**
     * ALPS `doDisablePlugin` に対応する POST 操作。
     * @psalm-taint-source input $pluginCode
     */
    #[Alps('doDisablePlugin')]
    #[JsonSchema(schema: 'post-admin-plugin-disable.json', params: 'post-admin-plugin-disable.param.json')]
    #[Link(rel: 'goPluginList', href: 'page://self/admin/plugin-list', method: 'get')]
    #[CsrfProtected]
    public function onPost(string $pluginCode): static
    {
        try {
            $final = ($this->becoming)(new DisablePluginInput(pluginCode: $pluginCode));
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
            $this->code = 409;
            $this->body = ['message' => 'プラグインがインストールされていません。'];

            return $this;
        }

        assert($final instanceof PluginDisabled);

        $this->code = Code::OK;
        $this->body = [
            'pluginCode' => $final->pluginCode,
            'enabled' => $final->enabled,
            'changed' => $final->changed,
        ];

        return $this;
    }
}
