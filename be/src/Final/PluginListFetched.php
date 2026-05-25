<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Entity\PluginEntity;
use MyVendor\BeMart\Be\Reason\Query\PluginStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use Ray\Di\Di\Inject;

use function array_map;
use function count;

/**
 * Plugin list fetched — Final, admin-side plugin registry projection.
 *
 *   GetPluginListInput → PluginListFetched (Direct, safe read)
 *
 * AUTHZ — admin firewall (Wave 4 contract): AdminSession::adminId() ===
 * null raises UnauthorizedAdminAccessException, which the BEAR layer
 * maps to 403.
 *
 * Public surface — shallow projection of PluginEntity, mirroring the
 * CustomerListFetched / AdminOrderListFetched conventions. The full
 * PluginEntity is not leaked into the HTTP body; the admin grid only
 * needs identification + lifecycle state.
 */
final readonly class PluginListFetched
{
    /** @var list<array{pluginCode: string, pluginName: string, pluginVersion: string, installed: bool, enabled: bool}> */
    public array $plugins;

    public int $count;

    public function __construct(
        #[Inject] AdminSessionInterface $adminSession,
        #[Inject] PluginStorageInterface $pluginStorage,
    ) {
        if ($adminSession->adminId() === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $rows = $pluginStorage->listAll();

        $this->plugins = array_map(
            static fn (PluginEntity $p): array => [
                'pluginCode' => $p->pluginCode,
                'pluginName' => $p->pluginName,
                'pluginVersion' => $p->version,
                'installed' => $p->installed,
                'enabled' => $p->enabled,
            ],
            $rows,
        );
        $this->count = count($rows);
    }
}
