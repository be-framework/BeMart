<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\PluginEntity;
use MyVendor\BeMart\Be\Reason\Query\Result\PluginEnablement;
use Ray\MediaQuery\Annotation\DbQuery;

/**
 * Plugin lifecycle — unified Query + Command (Wave 8 first iteration).
 *
 * Same CQRS-relaxed convention as {@see FavoriteStorageInterface} /
 * {@see AddressStorageInterface}: read + write methods share one
 * interface while the workload stays trivially small. Phase 2 can
 * split into PluginQuery / PluginCommand if the lifecycle pipeline
 * grows complex enough to warrant the separation.
 *
 * State surface (see {@see PluginEntity}):
 *
 *   - list()                                    → all plugin rows (any state)
 *   - findByCode(string $code)                     → one plugin (any state) or null
 *   - install(string $code, string $name, string $version): void
 *         Idempotent: a re-install of an already-installed plugin is
 *         a no-op (no exception). The real EC-CUBE pipeline downloads
 *         + unzips + runs migrations + clears cache; for migration
 *         scope this is STUBBED — we simply flip `installed=true` on
 *         the matching fixture record (or create a new row if there
 *         is no record yet for the code). The stub is intentional and
 *         documented by the PluginStorageInterface contract.
 *   - uninstall(string $code): void
 *         Silently no-op when the plugin is not installed (idempotent
 *         per ALPS `type=idempotent`). Removes the row entirely so a
 *         subsequent list() does not surface the uninstalled stub.
 *   - setEnabled(string $code, bool $enabled): void
 *         Only valid for *installed* plugins. Raises
 *         {@see \MyVendor\BeMart\Be\Exception\PluginNotInstalledException}
 *         if the plugin row exists but is not installed; raises
 *         {@see \MyVendor\BeMart\Be\Exception\PluginNotFoundException}
 *         if the plugin code is unknown entirely. Idempotent on the
 *         enabled value itself — setting enabled=true on an already-
 *         enabled plugin is a no-op (no exception, no spurious write).
 */
interface PluginStorageInterface
{
    /** @return list<PluginEntity> */
    #[DbQuery('plugin_list_all')]
    public function list(): array;

    #[DbQuery('plugin_find_by_code')]
    public function item(string $pluginCode): PluginEntity|null;

    #[DbQuery('plugin_install')]
    public function install(string $pluginCode, string $pluginName, string $version): void;

    #[DbQuery('plugin_uninstall')]
    public function uninstall(string $pluginCode): void;

    #[DbQuery('plugin_set_enabled')]
    public function setEnabled(string $pluginCode, bool $enabled): PluginEnablement;
}
