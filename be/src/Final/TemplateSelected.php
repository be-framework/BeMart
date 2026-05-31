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
 * Template selected — Final, proof an admin activated a design template
 * (doSelectTemplate).
 *
 *   SelectTemplateInput → TemplateSelected   (Direct, idempotent)
 *
 * AUTHZ ladder: no admin session → 403; unknown template → 404. The
 * asset-deploy side-effect is delegated to {@see TemplateCompatibilityInterface}.
 */
final readonly class TemplateSelected
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

        $templates->select($templateId);
        $this->templateId = $templateId;
    }
}
