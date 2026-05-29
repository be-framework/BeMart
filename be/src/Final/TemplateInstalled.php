<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Service\TemplateCompatibilityInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Template installed — Final, proof an admin registered a new design
 * template (doInstallTemplate).
 *
 *   InstallTemplateInput → TemplateInstalled   (Direct, unsafe)
 *
 * AUTHZ: no admin session → UnauthorizedAdminAccessException (403). The
 * archive unpack + asset deploy is delegated to
 * {@see TemplateCompatibilityInterface}, which returns the new id.
 */
final readonly class TemplateInstalled
{
    public string $templateId;
    public string $templateCode;
    public string $templateName;

    public function __construct(
        #[Input] string $templateCode,
        #[Input] string $templateName,
        #[Inject] AdminSession $adminSession,
        #[Inject] TemplateCompatibilityInterface $templates,
    ) {
        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $this->templateId = $templates->install($templateCode, $templateName);
        $this->templateCode = $templateCode;
        $this->templateName = $templateName;
    }
}
