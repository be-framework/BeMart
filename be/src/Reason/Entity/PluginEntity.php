<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Entity;

/**
 * Plugin entity — projection of EC-CUBE 4.3 dtb_plugin row.
 *
 * EC-CUBE plugins have a two-axis lifecycle:
 *   - installed: bool   — the plugin's files exist + migrations applied
 *   - enabled:   bool   — the plugin is being loaded into the container
 *
 * Valid combinations:
 *   - (installed=false, enabled=false) — uninstalled, just metadata
 *   - (installed=true,  enabled=false) — installed but disabled
 *   - (installed=true,  enabled=true)  — installed and active
 *
 * The fourth combination (installed=false, enabled=true) is forbidden —
 * the StorageInterface::setEnabled contract refuses it.
 *
 * NOTE: In the real EC-CUBE plugin system, install/uninstall is a
 * non-trivial pipeline (download → unzip → composer require → run
 * migrations → cache clear), and enable/disable triggers
 * container/routes regeneration. The migration scope only requires
 * the BEAR layer to be able to *call into* that pipeline; the Be
 * domain models the state surface, and the FakePluginStorage stubs
 * the actual install logic by simply flipping `installed=true` on a
 * fixture record matching the pluginCode passed in.
 */
final readonly class PluginEntity
{
    public function __construct(
        public string $pluginCode,
        public string $pluginName,
        public string $version,
        public bool $installed,
        public bool $enabled,
    ) {
    }
}
