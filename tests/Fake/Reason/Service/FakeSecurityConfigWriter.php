<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Fake\Service;

use MyVendor\BeMart\Be\Reason\Service\SecurityConfigWriterInterface;
use Override;

use function array_merge;

/**
 * Recording fake for the security-config boundary. Tests assert that
 * `doUpdateSecurity` wrote the expected settings exactly once.
 */
final class FakeSecurityConfigWriter implements SecurityConfigWriterInterface
{
    /** @var list<array<string, string>> */
    public array $writes = [];

    /** @var array<string, string> */
    public array $settings = [
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
        $this->writes[] = $settings;
        $this->settings = array_merge($this->settings, $settings);
    }

    /** @return array<string, string> */
    #[Override]
    public function read(): array
    {
        return $this->settings;
    }
}
