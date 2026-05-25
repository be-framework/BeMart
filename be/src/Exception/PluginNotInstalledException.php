<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

/**
 * Raised when an admin attempts to enable/disable a plugin whose row
 * exists in the registry but is currently in the uninstalled state.
 * EC-CUBE's lifecycle forbids the (installed=false, enabled=*) combo;
 * enable/disable are valid transitions only on installed plugins.
 *
 * The resource layer maps this to 409 Conflict — the request itself is
 * well-formed (the plugin exists) but the current state forbids the
 * requested transition. Distinct from {@see PluginNotFoundException}
 * (no such row at all).
 */
#[Message([
    'en' => 'Plugin is not installed.',
    'ja' => 'プラグインがインストールされていません。',
])]
final class PluginNotInstalledException extends DomainException
{
}
