<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

/**
 * Design-template management boundary (`doSelectTemplate` /
 * `doDeleteTemplate` / `doDownloadTemplate` / `doInstallTemplate`).
 *
 * EC-CUBE's `TemplateController` activates / deletes templates, zips a
 * template directory for download, and unpacks an uploaded archive into
 * the public template path. Those filesystem / public-asset side-effects
 * stay behind this boundary; the Be Finals depend only on this interface.
 */
interface TemplateCompatibilityInterface
{
    public function exists(string $templateId): bool;

    /** Activate the named template (apply it as the current theme). */
    public function select(string $templateId): void;

    /** Return the currently active template id when it can be read back. */
    public function selected(): string|null;

    public function delete(string $templateId): void;

    public function download(string $templateId): TemplateArchive;

    /** Install an uploaded template archive; returns the new template id. */
    public function install(string $code, string $name, string $archiveName, int $archiveSize): string;
}
