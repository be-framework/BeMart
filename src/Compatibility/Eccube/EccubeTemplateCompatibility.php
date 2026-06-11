<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Compatibility\Eccube;

use MyVendor\BeMart\Be\Reason\Entity\TemplateEntity;
use MyVendor\BeMart\Be\Reason\Query\TemplateStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\TemplateArchive;
use MyVendor\BeMart\Be\Reason\Service\TemplateCompatibilityInterface;
use Override;

use function array_key_exists;
use function dirname;
use function file_get_contents;
use function file_put_contents;
use function getenv;
use function is_dir;
use function is_file;
use function is_string;
use function max;
use function md5;
use function mkdir;
use function sprintf;
use function trim;
use function unlink;

/**
 * EC-CUBE-compatible design-template management boundary.
 *
 * Existence is checked against the live template storage. Activation keeps
 * a small ignored runtime marker so HTTP/browser requests can read back the
 * current selection without deploying public assets. Wiring the real
 * template directory zip/unpack + asset deployment is the production
 * cutover residual (migration-status §4).
 */
final class EccubeTemplateCompatibility implements TemplateCompatibilityInterface
{
    /** @var array<string, bool> installed template ids added at runtime */
    private array $installed = [];

    /** @var array<string, bool> ids removed at runtime */
    private array $deleted = [];

    private readonly string $selectedTemplateFile;

    public function __construct(
        private readonly TemplateStorageInterface $templates,
        string|null $selectedTemplateFile = null,
    ) {
        $this->selectedTemplateFile = $selectedTemplateFile ?? $this->defaultSelectedTemplateFile();
    }

    #[Override]
    public function exists(string $templateId): bool
    {
        if (array_key_exists($templateId, $this->deleted)) {
            return false;
        }

        if (array_key_exists($templateId, $this->installed)) {
            return true;
        }

        foreach ($this->templates->list() as $template) {
            /** @var TemplateEntity $template */
            if ($template->templateId === $templateId) {
                return true;
            }
        }

        return false;
    }

    #[Override]
    public function select(string $templateId): void
    {
        $dir = dirname($this->selectedTemplateFile);
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        file_put_contents($this->selectedTemplateFile, $templateId);
    }

    #[Override]
    public function selected(): string|null
    {
        $templateId = $this->selectedTemplateId();
        if ($templateId === null || ! $this->exists($templateId)) {
            return null;
        }

        return $templateId;
    }

    #[Override]
    public function delete(string $templateId): void
    {
        $wasSelected = $this->selectedTemplateId() === $templateId;
        $this->deleted[$templateId] = true;
        unset($this->installed[$templateId]);
        $this->templates->delete($templateId);
        if ($wasSelected && is_file($this->selectedTemplateFile)) {
            unlink($this->selectedTemplateFile);
        }
    }

    #[Override]
    public function download(string $templateId): TemplateArchive
    {
        $fileName = sprintf('%s.zip', $templateId);

        // Minimal zip header so the body is a recognisable archive; the
        // real per-file zip packing is the cutover residual.
        return new TemplateArchive("PK\x03\x04", $fileName, 'attachment; filename="' . $fileName . '"');
    }

    #[Override]
    public function install(string $code, string $name, string $archiveName, int $archiveSize): string
    {
        $templateId = (string) $this->nextTemplateId();
        $this->templates->put(new TemplateEntity($templateId, $name, 10), $code);
        $this->installed[$templateId] = true;

        return $templateId;
    }

    private function nextTemplateId(): int
    {
        $next = 1;
        foreach ($this->templates->list() as $template) {
            $next = max($next, ((int) $template->templateId) + 1);
        }

        return $next;
    }

    private function selectedTemplateId(): string|null
    {
        if (! is_file($this->selectedTemplateFile)) {
            return null;
        }

        $templateId = trim((string) file_get_contents($this->selectedTemplateFile));

        return $templateId === '' ? null : $templateId;
    }

    private function defaultSelectedTemplateFile(): string
    {
        $databaseUrl = getenv('DATABASE_URL');
        $suffix = is_string($databaseUrl) && $databaseUrl !== '' ? md5($databaseUrl) : 'default';

        return dirname(__DIR__, 3) . '/var/tmp/template-active-' . $suffix . '.txt';
    }
}
