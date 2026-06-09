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
 *   - put(MailTemplateEntity $entity)     → upsert a seeded/template row
 *   - update(MailTemplateEntity $entity)  → replace subject
 *   - delete(mailTemplateId)              → remove a row
 *
 * The public admin Resource migration scope only covers subject changes.
 * Workflow tests may still seed a row through this storage boundary.
 * The `mailTemplateId` MUST refer to an existing row for update(),
 * otherwise update() raises
 * {@see \MyVendor\BeMart\Be\Exception\MailTemplateNotFoundException}.
 */
interface MailTemplateStorageInterface
{
    /** @return list<MailTemplateEntity> */
    #[DbQuery('tmail_template_list')]
    public function list(): array;

    #[DbQuery('tmail_template_get')]
    public function item(int $mailTemplateId): MailTemplateEntity|null;

    #[DbQuery('tmail_template_put')]
    public function put(MailTemplateEntity $entity): void;

    #[DbQuery('tmail_template_update')]
    public function update(MailTemplateEntity $entity): MailTemplateUpdate;

    #[DbQuery('tmail_template_delete')]
    public function delete(int $mailTemplateId): void;
}
