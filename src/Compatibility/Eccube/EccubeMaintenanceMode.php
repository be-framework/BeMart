<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Compatibility\Eccube;

use MyVendor\BeMart\Be\Reason\Service\MaintenanceModeInterface;
use Override;

use function dirname;
use function file_exists;
use function file_put_contents;
use function is_dir;
use function mkdir;
use function unlink;

/**
 * EC-CUBE-compatible maintenance-mode boundary.
 *
 * Persists the maintenance flag in a local marker file so browser/HTTP
 * requests can read back the state after `doToggleMaintenance`. Flipping
 * EC-CUBE's production marker file remains the cutover residual; BeMart
 * keeps this demo boundary under ignored `var/tmp`.
 */
final class EccubeMaintenanceMode implements MaintenanceModeInterface
{
    private readonly string $flagFile;

    public function __construct(string|null $flagFile = null)
    {
        $this->flagFile = $flagFile ?? dirname(__DIR__, 3) . '/var/tmp/maintenance-mode.flag';
    }

    #[Override]
    public function isEnabled(): bool
    {
        return file_exists($this->flagFile);
    }

    #[Override]
    public function setEnabled(bool $enabled): void
    {
        if ($enabled) {
            $dir = dirname($this->flagFile);
            if (! is_dir($dir)) {
                mkdir($dir, 0777, true);
            }

            file_put_contents($this->flagFile, 'enabled');

            return;
        }

        if (file_exists($this->flagFile)) {
            unlink($this->flagFile);
        }
    }
}
