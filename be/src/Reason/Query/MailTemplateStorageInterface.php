<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\MailTemplateEntity;

/**
 * Mail template storage — unified Query + Command (Wave 8).
 *
 *   - list()                              → every template, sorted by id
 *   - findById(int $mailTemplateId)       → one template or null
 *   - update(MailTemplateEntity $entity)  → replace subject / body / htmlBody
 *
 * The migration scope only covers UPDATE of subject + body + htmlBody.
 * Creating a new template (which requires setting the underlying
 * file_name) is Phase 2 — for now the `mailTemplateId` MUST refer to
 * an existing seeded row, otherwise update() raises
 * {@see \MyVendor\BeMart\Be\Exception\MailTemplateNotFoundException}.
 */
interface MailTemplateStorageInterface
{
    /** @return list<MailTemplateEntity> */
    public function list(): array;

    public function findById(int $mailTemplateId): MailTemplateEntity|null;

    public function update(MailTemplateEntity $entity): void;
}
