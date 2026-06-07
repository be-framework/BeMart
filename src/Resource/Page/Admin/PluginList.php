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
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\PluginInstalled;
use MyVendor\BeMart\Be\Final\PluginListFetched;
use MyVendor\BeMart\Be\Input\GetPluginListInput;
use MyVendor\BeMart\Be\Input\InstallPluginInput;
use BEAR\Resource\Annotation\JsonSchema;

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
    ) {
    }

    /** ALPS `goPluginList` に対応する GET 操作。 */
    #[Alps('goPluginList')]
    #[JsonSchema(schema: 'get-admin-plugin-list.json')]
    #[Link(rel: 'doEnablePlugin', href: 'page://self/admin/plugin-enable', method: 'post')]
    #[Link(rel: 'doDisablePlugin', href: 'page://self/admin/plugin-disable', method: 'post')]
    #[Link(rel: 'doUninstallPlugin', href: 'page://self/admin/plugin', method: 'delete')]
    public function onGet(): static
    {
        $final = ($this->becoming)(new GetPluginListInput());

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
     */
    #[Alps('doInstallPlugin')]
    #[JsonSchema(schema: 'post-admin-plugin-list.json', params: 'post-admin-plugin-list.param.json')]
    #[Link(rel: 'goPluginList', href: 'page://self/admin/plugin-list', method: 'get')]
    #[CsrfProtected]
    public function onPost(
        string $pluginCode,
        string $pluginName,
        string $pluginVersion,
    ): static {
        $final = ($this->becoming)(new InstallPluginInput(
            pluginCode: $pluginCode,
            pluginName: $pluginName,
            pluginVersion: $pluginVersion,
        ));

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
