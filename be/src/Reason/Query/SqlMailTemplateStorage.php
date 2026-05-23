<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Exception\MailTemplateNotFoundException;
use MyVendor\BeMart\Be\Reason\Entity\MailTemplateEntity;
use Override;

final class SqlMailTemplateStorage implements MailTemplateStorageInterface
{
    public function __construct(private readonly InternalDbQueryInterface $db) {}

    /** @return list<MailTemplateEntity> */
    #[Override]
    public function list(): array
    {
        return array_map($this->hydrate(...), $this->db->tmail_template_list());
    }

    #[Override]
    public function findById(int $mailTemplateId): MailTemplateEntity|null
    {
        $row = $this->db->tmail_template_get(id: $mailTemplateId);
        return $row === null ? null : $this->hydrate($row);
    }

    #[Override]
    public function update(MailTemplateEntity $entity): void
    {
        if ($this->db->tmail_template_exists(id: $entity->mailTemplateId) === null) {
            throw new MailTemplateNotFoundException();
        }
        $this->db->tmail_template_update(id: $entity->mailTemplateId, subject: $entity->subject);
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): MailTemplateEntity
    {
        return new MailTemplateEntity(
            mailTemplateId: (int) $row['id'],
            mailTemplateName: (string) ($row['name'] ?? ''),
            fileName: (string) ($row['file_name'] ?? ''),
            subject: (string) ($row['mail_subject'] ?? ''),
        );
    }
}
