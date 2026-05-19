<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\PluginVersionFormatException;

use function mb_strlen;
use function preg_match;
use function trim;

/**
 * Plugin version string — EC-CUBE 4.3 dtb_plugin.version.
 *
 * Non-empty, <= 32 chars, [A-Za-z0-9._-] only. The EC-CUBE installer
 * accepts free-form version strings; the format check is intentionally
 * permissive (semver, dot-only, and `1.0-beta` style are all valid).
 *
 * @link https://schema.org/softwareVersion
 */
final class PluginVersion
{
    #[Validate]
    public function validate(string $pluginVersion): void
    {
        if (trim($pluginVersion) === '' || mb_strlen($pluginVersion) > 32) {
            throw new PluginVersionFormatException();
        }

        if (! preg_match('/^[A-Za-z0-9._-]+$/', $pluginVersion)) {
            throw new PluginVersionFormatException();
        }
    }
}
