<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Fake\Service;

use MyVendor\BeMart\Be\Reason\Service\TemplateArchive;
use MyVendor\BeMart\Be\Reason\Service\TemplateCompatibilityInterface;
use Override;

use function array_key_exists;
use function sprintf;

/**
 * Deterministic design-template boundary for tests. `default` is a
 * pre-existing template; install/delete mutate the in-memory set.
 */
final class FakeTemplateCompatibility implements TemplateCompatibilityInterface
{
    /** @var array<string, bool> */
    public array $templates = ['default' => true];

    /** @var list<string> */
    public array $selected = [];

    /** @var list<string> */
    public array $installed = [];

    #[Override]
    public function exists(string $templateId): bool
    {
        return array_key_exists($templateId, $this->templates) && $this->templates[$templateId];
    }

    #[Override]
    public function select(string $templateId): void
    {
        $this->selected[] = $templateId;
    }

    #[Override]
    public function delete(string $templateId): void
    {
        $this->templates[$templateId] = false;
    }

    #[Override]
    public function download(string $templateId): TemplateArchive
    {
        $fileName = sprintf('%s.zip', $templateId);

        return new TemplateArchive("PK\x03\x04", $fileName, 'attachment; filename="' . $fileName . '"');
    }

    #[Override]
    public function install(string $code, string $name): string
    {
        $templateId = 'tpl_' . $code;
        $this->templates[$templateId] = true;
        $this->installed[] = $templateId;

        return $templateId;
    }
}
