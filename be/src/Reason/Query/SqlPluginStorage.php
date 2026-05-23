<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Exception\PluginNotFoundException;
use MyVendor\BeMart\Be\Exception\PluginNotInstalledException;
use MyVendor\BeMart\Be\Reason\Entity\PluginEntity;
use Override;

final class SqlPluginStorage implements PluginStorageInterface
{
    private const DISCRIMINATOR = 'plugin';
    private const SOURCE_DEFAULT = 0;

    public function __construct(private readonly MediaQueryExecutor $db) {}

    /** @return list<PluginEntity> sorted by pluginCode ascending */
    #[Override]
    public function listAll(): array
    {
        return array_map($this->hydrate(...), $this->db->rows('plugin_list_all'));
    }

    #[Override]
    public function findByCode(string $pluginCode): PluginEntity|null
    {
        $row = $this->db->row('plugin_find_by_code', ['code' => $pluginCode]);

        return $row === null ? null : $this->hydrate($row);
    }

    #[Override]
    public function install(string $pluginCode, string $pluginName, string $version): void
    {
        $existing = $this->findByCode($pluginCode);
        if ($existing !== null && $existing->installed) {
            return;
        }

        if ($existing !== null) {
            $this->db->exec('plugin_mark_installed', ['code' => $pluginCode]);

            return;
        }

        $this->db->exec('plugin_insert', [
            'name' => $pluginName,
            'code' => $pluginCode,
            'version' => $version,
            'source' => self::SOURCE_DEFAULT,
            'discriminator' => self::DISCRIMINATOR,
        ]);
    }

    #[Override]
    public function uninstall(string $pluginCode): void
    {
        $this->db->exec('plugin_uninstall', ['code' => $pluginCode]);
    }

    #[Override]
    public function setEnabled(string $pluginCode, bool $enabled): void
    {
        $existing = $this->findByCode($pluginCode);
        if ($existing === null) {
            throw new PluginNotFoundException();
        }

        if (! $existing->installed) {
            throw new PluginNotInstalledException();
        }

        if ($existing->enabled === $enabled) {
            return;
        }

        $this->db->exec('plugin_set_enabled', [
            'enabled' => $enabled ? 1 : 0,
            'code' => $pluginCode,
        ]);
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): PluginEntity
    {
        return new PluginEntity(
            pluginCode: (string) $row['code'],
            pluginName: (string) $row['name'],
            version: (string) $row['version'],
            installed: (bool) (int) $row['initialized'],
            enabled: (bool) (int) $row['enabled'],
        );
    }
}
