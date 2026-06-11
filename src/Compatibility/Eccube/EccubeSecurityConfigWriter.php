<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Compatibility\Eccube;

use MyVendor\BeMart\Be\Reason\Service\SecurityConfigWriterInterface;
use Override;

use function array_merge;
use function dirname;
use function file_get_contents;
use function file_put_contents;
use function is_array;
use function is_dir;
use function is_file;
use function json_decode;
use function json_encode;
use function mkdir;

use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

/**
 * EC-CUBE-compatible security-config boundary.
 *
 * Holds the host allow/deny + trusted-hosts settings behind the
 * {@see SecurityConfigWriterInterface} contract. The default starts from
 * EC-CUBE 4.3's out-of-the-box values and keeps updates in an ignored
 * BeMart runtime file so real HTTP requests can read back the setting
 * across requests without touching production Symfony config. Persisting
 * to the real runtime config and reloading the Symfony firewall remains
 * the production cutover residual (migration-status §4).
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
        $file = $this->stateFile();
        $dir = dirname($file);
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        file_put_contents(
            $file,
            json_encode($this->settings, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n",
        );
    }

    /** @return array<string, string> */
    #[Override]
    public function read(): array
    {
        $file = $this->stateFile();
        if (is_file($file)) {
            $decoded = json_decode((string) file_get_contents($file), true, flags: JSON_THROW_ON_ERROR);
            if (is_array($decoded)) {
                /** @var array<string, string> $settings */
                $settings = $decoded;

                return array_merge($this->settings, $settings);
            }
        }

        return $this->settings;
    }

    private function stateFile(): string
    {
        return dirname(__DIR__, 3) . '/var/tmp/security-config.json';
    }
}
