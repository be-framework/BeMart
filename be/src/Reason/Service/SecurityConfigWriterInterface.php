<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

/**
 * Security-settings boundary (`doUpdateSecurity`).
 *
 * EC-CUBE 4.3's security screen writes host allow/deny lists and the
 * trusted-hosts pattern into runtime config (`eccube.allow_host`,
 * `trusted_hosts`, …). That config-file side-effect — and the Symfony
 * firewall reload it implies — stays behind this boundary; the Be Final
 * ({@see \MyVendor\BeMart\Be\Final\SecuritySettingsUpdated}) depends only on
 * this interface and the implementation decides whether to persist the
 * change to config, the DB, or (the production-neutral default) hold it
 * in memory until the production cutover wires the real config writer.
 */
interface SecurityConfigWriterInterface
{
    /** @param array<string, string> $settings */
    public function write(array $settings): void;

    /** @return array<string, string> */
    public function read(): array;
}
