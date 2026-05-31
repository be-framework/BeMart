<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Compatibility\Eccube;

use MyVendor\BeMart\Be\Reason\Service\SecurityConfigWriterInterface;
use Override;

use function array_merge;

/**
 * EC-CUBE-compatible security-config boundary.
 *
 * Holds the host allow/deny + trusted-hosts settings behind the
 * {@see SecurityConfigWriterInterface} contract. The default starts from
 * EC-CUBE 4.3's out-of-the-box values and keeps updates in process (bound
 * as a singleton) so `doUpdateSecurity` is exercisable end to end.
 * Persisting to the real runtime config and reloading the Symfony
 * firewall is the production cutover residual (migration-status §4) — by
 * design BeMart applies the change on the safe side rather than
 * reproducing Symfony's runtime.
 */
final class EccubeSecurityConfigWriter implements SecurityConfigWriterInterface
{
    /** @var array<string, string> */
    private array $settings = [
        'admin_allow_hosts' => '',
        'admin_deny_hosts' => '',
        'front_allow_hosts' => '',
        'front_deny_hosts' => '',
        'trusted_hosts' => '^localhost$',
    ];

    /** @param array<string, string> $settings */
    #[Override]
    public function write(array $settings): void
    {
        $this->settings = array_merge($this->settings, $settings);
    }

    /** @return array<string, string> */
    #[Override]
    public function read(): array
    {
        return $this->settings;
    }
}
