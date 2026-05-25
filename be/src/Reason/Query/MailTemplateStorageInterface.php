<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\MailTemplateEntity;
use MyVendor\BeMart\Be\Reason\Query\Result\MailTemplateUpdate;
use Ray\MediaQuery\Annotation\DbQuery;

/**
 * Mail template storage — unified Query + Command (Wave 8).
 *
 *   - list()                              → every template, sorted by id
 *   - item(int $mailTemplateId)       → one template or null
 *   - update(MailTemplateEntity $entity)  → replace subject
 *
 * The migration scope only covers subject changes. Creating a new
 * template (which requires setting the underlying file_name) is
 * Phase 2 — for now the `mailTemplateId` MUST refer to an existing
 * seeded row, otherwise update() raises
 * {@see \MyVendor\BeMart\Be\Exception\MailTemplateNotFoundException}.
 */
interface MailTemplateStorageInterface
{
    /** @return list<MailTemplateEntity> */
    #[DbQuery('tmail_template_list')]
    public function list(): array;

    #[DbQuery('tmail_template_get')]
    public function item(int $mailTemplateId): MailTemplateEntity|null;

    #[DbQuery('tmail_template_update')]
    public function update(MailTemplateEntity $entity): MailTemplateUpdate;
}
