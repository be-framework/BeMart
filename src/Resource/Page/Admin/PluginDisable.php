<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin;

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
use MyVendor\BeMart\Be\Reason\Service\CsrfTokenInterface;

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
        private readonly CsrfTokenInterface $csrf,
    ) {
    }

    /**
     * @psalm-taint-source input $pluginCode
     * @psalm-taint-source input $csrfToken
     */
    #[Link(rel: 'goPluginList', href: 'page://self/admin/plugin-list', method: 'get')]
    public function onPost(string $pluginCode, string|null $csrfToken = null): static
    {
        if (! $this->csrf->isValid($csrfToken)) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'Invalid or missing CSRF token.'];

            return $this;
        }

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
