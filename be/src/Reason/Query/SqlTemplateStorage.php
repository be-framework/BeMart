<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\TemplateEntity;
use Override;

final class SqlTemplateStorage implements TemplateStorageInterface
{
    public function __construct(private readonly InternalDbQueryInterface $db) {}

    /** @return list<TemplateEntity> */
    #[Override]
    public function list(): array
    {
        return array_map(
            static fn (array $row): TemplateEntity => new TemplateEntity(
                (string) (int) $row['id'],
                (string) ($row['template_name'] ?? ''),
                (int) $row['device_type_id'],
            ),
            $this->db->ttemplate_list(),
        );
    }
}
