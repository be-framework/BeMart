<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

/**
 * Raised when an admin attempts to enable/disable/uninstall a plugin
 * by a `pluginCode` that does not exist in the plugin registry at all.
 *
 * The resource layer maps this to 404. Distinct from
 * {@see PluginNotInstalledException}, which fires when the row exists
 * but is in the uninstalled state — that distinction is intentional
 * so the admin UI can show a different error message ("unknown plugin"
 * vs "install the plugin first").
 */
#[Message([
    'en' => 'Plugin not found.',
    'ja' => 'プラグインが見つかりませんでした。',
])]
final class PluginNotFoundException extends DomainException
{
}
