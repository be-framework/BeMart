<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Exception\PluginNotFoundException;
use MyVendor\BeMart\Be\Exception\PluginNotInstalledException;
use MyVendor\BeMart\Be\Reason\Entity\PluginEntity;
use Override;

use function array_values;
use function strcmp;
use function usort;
use MyVendor\BeMart\Be\Reason\Query\Result\PluginEnablementResult;

/**
 * In-memory plugin store.
 *
 * Seeded with 2 demo plugins matching the contract spec:
 *   - "Sample/SamplePlugin"     — installed + enabled
 *   - "Sample/DisabledPlugin"   — installed + disabled
 *
 * Singleton-bound so reads in the same Becoming chain see the
 * install/uninstall/enable/disable writes from the same chain.
 *
 * INSTALL STUB: the real EC-CUBE install pipeline is non-trivial
 * (download ZIP → unzip into app/Plugin → composer require → run
 * migrations → cache clear → container/routes regen). The migration
 * scope only requires the BEAR layer to call into the storage; the
 * fake STUBS the actual install by simply flipping `installed=true`
 * on a fixture row matching the pluginCode, or creating a new row
 * with the provided name/version when no fixture exists for that
 * code yet. enable/disable are equivalent flips with no
 * container/routes regen wired in.
 */
final class FakePluginStorage implements PluginStorageInterface
{
    public const SEED_ENABLED_CODE = 'Sample/SamplePlugin';
    public const SEED_DISABLED_CODE = 'Sample/DisabledPlugin';

    /** @var array<string, PluginEntity> keyed by pluginCode */
    private array $byCode = [];

    public function __construct()
    {
        $this->byCode[self::SEED_ENABLED_CODE] = new PluginEntity(
            pluginCode: self::SEED_ENABLED_CODE,
            pluginName: 'Sample Plugin',
            version: '1.0.0',
            installed: true,
            enabled: true,
        );
        $this->byCode[self::SEED_DISABLED_CODE] = new PluginEntity(
            pluginCode: self::SEED_DISABLED_CODE,
            pluginName: 'Disabled Sample Plugin',
            version: '1.0.0',
            installed: true,
            enabled: false,
        );
    }

    /** @return list<PluginEntity> */
    #[Override]
    public function listAll(): array
    {
        $rows = array_values($this->byCode);
        usort(
            $rows,
            static fn (PluginEntity $a, PluginEntity $b): int => strcmp($a->pluginCode, $b->pluginCode),
        );

        return $rows;
    }

    #[Override]
    public function findByCode(string $pluginCode): PluginEntity|null
    {
        return $this->byCode[$pluginCode] ?? null;
    }

    #[Override]
    public function install(string $pluginCode, string $pluginName, string $version): void
    {
        $existing = $this->byCode[$pluginCode] ?? null;
        if ($existing !== null && $existing->installed) {
            // Idempotent: re-install of an already-installed plugin
            // is a no-op (the real pipeline would also short-circuit
            // when the row already exists in dtb_plugin).
            return;
        }

        // STUB — see class-level doc. Flip the `installed` flag without
        // exercising the real install pipeline. A brand-new row carries
        // enabled=false by EC-CUBE convention: install does not auto-
        // enable; the admin must explicitly call doEnablePlugin.
        $this->byCode[$pluginCode] = new PluginEntity(
            pluginCode: $pluginCode,
            pluginName: $pluginName,
            version: $version,
            installed: true,
            enabled: false,
        );
    }

    #[Override]
    public function uninstall(string $pluginCode): void
    {
        $existing = $this->byCode[$pluginCode] ?? null;
        if ($existing === null || ! $existing->installed) {
            // Idempotent: uninstalling an unknown / already-uninstalled
            // plugin is silently a no-op (ALPS `type=idempotent`).
            return;
        }

        // Drop the row entirely so a subsequent listAll() does not
        // surface the uninstalled stub. The real pipeline removes
        // dtb_plugin row + reverses migrations + deletes files.
        unset($this->byCode[$pluginCode]);
    }

    #[Override]
    public function setEnabled(string $pluginCode, bool $enabled): PluginEnablementResult
    {
        $existing = $this->byCode[$pluginCode] ?? null;
        if ($existing === null) {
            throw new PluginNotFoundException();
        }

        if (! $existing->installed) {
            // setEnabled is only valid for installed plugins — the
            // (installed=false, enabled=true) combination is forbidden.
            throw new PluginNotInstalledException();
        }

        if ($existing->enabled === $enabled) {
            // Idempotent: no-op when the value already matches.
            return new PluginEnablementResult(false);
        }

        $this->byCode[$pluginCode] = new PluginEntity(
            pluginCode: $existing->pluginCode,
            pluginName: $existing->pluginName,
            version: $existing->version,
            installed: true,
            enabled: $enabled,
        );

        return new PluginEnablementResult(true);
    }
}
