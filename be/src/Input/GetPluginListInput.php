<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\PluginListFetched;

/**
 * Input for goPluginList — admin lists installed plugins.
 *
 *   GetPluginListInput → PluginListFetched (Final — Direct, safe read)
 *
 * Admin-only endpoint (AUTHZ in the Final via AdminSessionInterface).
 *
 * No request fields: the admin grid simply asks for "every plugin
 * the registry knows about". A future Wave 9 sweep may introduce
 * paging / state filters, but the initial migration ships the simple
 * listAll() projection — the plugin count is bounded in practice
 * (rarely more than a few dozen rows per shop).
 *
 * @link https://schema.org/SearchAction
 */
#[Be(PluginListFetched::class)]
final readonly class GetPluginListInput
{
    public function __construct()
    {
    }
}
