<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Compatibility\Eccube;

use MyVendor\BeMart\Be\Reason\Entity\TemplateEntity;
use MyVendor\BeMart\Be\Reason\Query\TemplateStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\TemplateArchive;
use MyVendor\BeMart\Be\Reason\Service\TemplateCompatibilityInterface;
use Override;

use function array_key_exists;
use function max;
use function sprintf;

/**
 * EC-CUBE-compatible design-template management boundary.
 *
 * Existence is checked against the live template storage; activation /
 * delete / install track state in process (bound as a singleton) so the
 * transitions are exercisable end to end. Wiring the real template
 * directory zip/unpack + public-asset deployment is the production
 * cutover residual (migration-status §4).
 */
final class EccubeTemplateCompatibility implements TemplateCompatibilityInterface
{
    /** @var array<string, bool> installed template ids added at runtime */
    private array $installed = [];

    /** @var array<string, bool> ids removed at runtime */
    private array $deleted = [];

    public function __construct(
        private readonly TemplateStorageInterface $templates,
    ) {
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
        // Activating a template is an asset-deploy side-effect — residual.
    }

    #[Override]
    public function delete(string $templateId): void
    {
        $this->deleted[$templateId] = true;
        unset($this->installed[$templateId]);
        $this->templates->delete($templateId);
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
    public function install(string $code, string $name): string
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
}
