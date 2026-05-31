<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\TemplateNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Service\TemplateArchive;
use MyVendor\BeMart\Be\Reason\Service\TemplateCompatibilityInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Template downloaded — Final, the zip archive of a design template
 * (doDownloadTemplate).
 *
 *   DownloadTemplateInput → TemplateDownloaded   (Direct, unsafe)
 *
 * AUTHZ ladder: no admin session → 403; unknown template → 404. The zip
 * body + headers come from {@see TemplateCompatibilityInterface}.
 */
final readonly class TemplateDownloaded
{
    public TemplateArchive $archive;

    public function __construct(
        #[Input] string $templateId,
        #[Inject] AdminSession $adminSession,
        #[Inject] TemplateCompatibilityInterface $templates,
    ) {
        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        if (! $templates->exists($templateId)) {
            throw new TemplateNotFoundException();
        }

        $this->archive = $templates->download($templateId);
    }
}
