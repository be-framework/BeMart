<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\TemplateNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Service\TemplateCompatibilityInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Template deleted — Final, proof an admin deleted a design template
 * (doDeleteTemplate).
 *
 *   DeleteTemplateInput → TemplateDeleted   (Direct, idempotent)
 *
 * AUTHZ ladder: no admin session → 403; unknown template → 404. The
 * file-removal is delegated to {@see TemplateCompatibilityInterface}.
 */
final readonly class TemplateDeleted
{
    public string $templateId;

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

        $templates->delete($templateId);
        $this->templateId = $templateId;
    }
}
