<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\PluginNameFormatException;

use function mb_strlen;
use function trim;

/**
 * Plugin display name — EC-CUBE 4.3 dtb_plugin.name.
 *
 * Non-empty, <= 255 chars. Free-form text (Japanese / English /
 * mixed), so there is no character-set restriction beyond length.
 */
final class PluginName
{
    #[Validate]
    public function validate(string $pluginName): void
    {
        if (trim($pluginName) === '' || mb_strlen($pluginName) > 255) {
            throw new PluginNameFormatException();
        }
    }
}
