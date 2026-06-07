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
use MyVendor\BeMart\Be\Final\PluginUninstalled;
use MyVendor\BeMart\Be\Input\UninstallPluginInput;
use BEAR\Resource\Annotation\JsonSchema;

use function assert;

/**
 * EC-CUBE doUninstallPlugin — プラグインをアンインストールする (Wave 8).
 *
 * DELETE on the Plugin sub-resource. Per ALPS the doUninstallPlugin
 * transition lives on `#Plugin` (the per-plugin descriptor), so it
 * makes sense to expose it as DELETE on a per-plugin URI. Enable /
 * disable are separate sub-resources (PluginEnable / PluginDisable)
 * to mirror the ALPS-level separation of the three idempotent
 * transitions.
 *
 * Failure mapping:
 *   - Invalid CSRF                          → 403
 *   - SemanticVariableException             → 400 (pluginCode format)
 *   - UnauthorizedAdminAccessException      → 403 (no admin session)
 *
 * Idempotency (ALPS `type=idempotent`): unknown / already-uninstalled
 * plugins resolve to a 200 with `wasInstalled=false` rather than 404 —
 * the request itself is well-formed and the post-condition (plugin not
 * installed) is satisfied. Same convention as AdminCustomerDeleted's
 * `alreadyDeleted` (Wave 6).
 *
 * STUB: the real EC-CUBE pipeline reverses migrations + deletes files
 * + clears cache. Migration scope STUBS this — the storage simply
 * drops the row.
 */
class Plugin extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
    ) {
    }

    /**
     * Wave 8: pluginCode is admin-form input (selected from the grid).
     *
     * @psalm-taint-source input $pluginCode
     */
    #[Alps('doUninstallPlugin')]
    #[JsonSchema(schema: 'delete-admin-plugin.json', params: 'delete-admin-plugin.param.json')]
    #[Link(rel: 'goPluginList', href: 'page://self/admin/plugin-list', method: 'get')]
    #[CsrfProtected]
    public function onDelete(string $pluginCode): static
    {
        try {
            $final = ($this->becoming)(new UninstallPluginInput(pluginCode: $pluginCode));
        } catch (SemanticVariableException $e) {
            $this->code = Code::BAD_REQUEST;
            $this->body = ['message' => $e->getErrors()->getMessages('ja')[0] ?? 'Invalid input.'];

            return $this;
        } catch (UnauthorizedAdminAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        assert($final instanceof PluginUninstalled);

        $this->code = Code::OK;
        $this->body = [
            'pluginCode' => $final->pluginCode,
            'wasInstalled' => $final->wasInstalled,
        ];

        return $this;
    }
}
