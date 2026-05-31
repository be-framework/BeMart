<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\TemplateInstalled;

/**
 * Input for `doInstallTemplate` — an admin uploads/registers a new design
 * template (Hard ActionRedirect completion). ALPS marks it `unsafe`. The
 * archive unpack + public-asset deploy is isolated in
 * {@see \MyVendor\BeMart\Be\Reason\Service\TemplateCompatibilityInterface}.
 */
#[Be(TemplateInstalled::class)]
final readonly class InstallTemplateInput
{
    /**
     * @psalm-taint-source input $templateCode
     * @psalm-taint-source input $templateName
     */
    public function __construct(
        public string $templateCode,
        public string $templateName,
    ) {
    }
}
