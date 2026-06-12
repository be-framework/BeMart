<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Entity\TemplateEntity;
use MyVendor\BeMart\Be\Reason\Query\TemplateStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Service\TemplateCompatibilityInterface;
use Ray\Di\Di\Inject;

use function array_map;
use function count;

/**
 * Admin template list fetched — Final (Wave 9).
 */
final readonly class AdminTemplateListFetched
{
    public int $count;

    /** @var list<array{templateId: string, templateName: string, deviceType: int, active: bool}> */
    public array $templates;

    public function __construct(
        #[Inject] AdminSession $adminSession,
        #[Inject] TemplateStorageInterface $templates,
        #[Inject] TemplateCompatibilityInterface $templateCompatibility,
    ) {
        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $rows = $templates->list();
        $selectedTemplateId = $templateCompatibility->selected();

        $this->count = count($rows);
        $this->templates = array_map(
            static fn (TemplateEntity $row): array => [
                'templateId' => $row->templateId,
                'templateName' => $row->templateName,
                'deviceType' => $row->deviceType,
                'active' => $row->templateId === $selectedTemplateId,
            ],
            $rows,
        );
    }
}
