<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\PluginInstalled;
use MyVendor\BeMart\Be\Final\PluginListFetched;
use MyVendor\BeMart\Be\Input\GetPluginListInput;
use MyVendor\BeMart\Be\Input\InstallPluginInput;
use MyVendor\BeMart\Be\Reason\Service\CsrfTokenInterface;

use function assert;

/**
 * EC-CUBE goPluginList + doInstallPlugin — プラグイン一覧 (Wave 8).
 *
 * Two affordances on one URI:
 *   - GET  → list every plugin in the registry (goPluginList, safe)
 *   - POST → install a new plugin (doInstallPlugin, unsafe + CSRF)
 *
 * Same URI / two verbs pattern as
 * {@see \MyVendor\BeMart\Resource\Page\Admin\CustomerList} (GET list,
 * POST does NOT live on CustomerList — but on this resource POST is
 * the install affordance per ALPS, which puts `doInstallPlugin` directly
 * on `#PluginList`).
 *
 * Admin-only. Both verbs surface
 * UnauthorizedAdminAccessException as 403.
 *
 * INSTALL STUB: see {@see \MyVendor\BeMart\Be\Reason\Query\PluginStorageInterface}.
 * The real EC-CUBE install pipeline (download / unzip / composer / migrate /
 * cache) is STUBBED — the storage simply flips `installed=true`.
 *
 * Hypermedia: GET lists every plugin and forward-declares the per-plugin
 * sub-resource affordances (enable / disable / uninstall).
 */
class PluginList extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
        private readonly CsrfTokenInterface $csrf,
    ) {
    }

    #[Link(rel: 'doEnablePlugin', href: 'page://self/admin/plugin-enable', method: 'post')]
    #[Link(rel: 'doDisablePlugin', href: 'page://self/admin/plugin-disable', method: 'post')]
    #[Link(rel: 'doUninstallPlugin', href: 'page://self/admin/plugin', method: 'delete')]
    public function onGet(): static
    {
        try {
            $final = ($this->becoming)(new GetPluginListInput());
        } catch (UnauthorizedAdminAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        assert($final instanceof PluginListFetched);

        $this->code = Code::OK;
        $this->body = [
            'plugins' => $final->plugins,
            'count' => $final->count,
        ];

        return $this;
    }

    /**
     * Wave 8: every form field is admin-form input.
     *
     * @psalm-taint-source input $pluginCode
     * @psalm-taint-source input $pluginName
     * @psalm-taint-source input $pluginVersion
     * @psalm-taint-source input $csrfToken
     */
    #[Link(rel: 'goPluginList', href: 'page://self/admin/plugin-list', method: 'get')]
    public function onPost(
        string $pluginCode,
        string $pluginName,
        string $pluginVersion,
        string|null $csrfToken = null,
    ): static {
        if (! $this->csrf->isValid($csrfToken)) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'Invalid or missing CSRF token.'];

            return $this;
        }

        try {
            $final = ($this->becoming)(new InstallPluginInput(
                pluginCode: $pluginCode,
                pluginName: $pluginName,
                pluginVersion: $pluginVersion,
            ));
        } catch (SemanticVariableException $e) {
            $this->code = Code::BAD_REQUEST;
            $this->body = [
                'message' => $e->getErrors()->getMessages('ja')[0] ?? 'Invalid input.',
            ];

            return $this;
        } catch (UnauthorizedAdminAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        assert($final instanceof PluginInstalled);

        $this->code = $final->alreadyInstalled ? Code::OK : Code::CREATED;
        $this->body = [
            'pluginCode' => $final->pluginCode,
            'pluginName' => $final->pluginName,
            'pluginVersion' => $final->pluginVersion,
            'installed' => $final->installed,
            'enabled' => $final->enabled,
            'alreadyInstalled' => $final->alreadyInstalled,
        ];

        return $this;
    }
}
