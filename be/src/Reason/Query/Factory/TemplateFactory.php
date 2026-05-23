<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query\Factory;

use MyVendor\BeMart\Be\Reason\Entity\TemplateEntity;

final class TemplateFactory
{
    public function factory(int|string $id, string $templateName, int|string $deviceTypeId): TemplateEntity
    {
        return new TemplateEntity((string) $id, $templateName, (int) $deviceTypeId);
    }
}
