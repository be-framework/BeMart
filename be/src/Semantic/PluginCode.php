<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\PluginCodeFormatException;

use function mb_strlen;
use function preg_match;
use function trim;

/**
 * Plugin code — EC-CUBE 4.3 dtb_plugin.code.
 *
 * Non-empty, <= 128 chars. The character set matches what the EC-CUBE
 * plugin installer accepts: PSR-4-style namespace fragments, so
 * letters, digits, `_`, `-`, `/`, `.`. The slash supports the standard
 * `Vendor/PluginName` form (e.g. `Sample/SamplePlugin`).
 */
final class PluginCode
{
    #[Validate]
    public function validate(string $pluginCode): void
    {
        if (trim($pluginCode) === '' || mb_strlen($pluginCode) > 128) {
            throw new PluginCodeFormatException();
        }

        if (! preg_match('#^[A-Za-z0-9._/\-]+$#', $pluginCode)) {
            throw new PluginCodeFormatException();
        }
    }
}
