<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Entity;

/**
 * Design template — projection of EC-CUBE dtb_template (Wave 9).
 *
 * Templates are filesystem-backed in EC-CUBE; ALPS only exposes the
 * list affordance. Create / update / delete (and the upload flow)
 * are Phase 2 scope.
 */
final readonly class TemplateEntity
{
    public function __construct(
        public string $templateId,
        public string $templateName,
        public int $deviceType,
    ) {
    }
}
