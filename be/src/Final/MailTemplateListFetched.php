<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Entity\MailTemplateEntity;
use MyVendor\BeMart\Be\Reason\Query\MailTemplateStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use Ray\Di\Di\Inject;

use function array_map;
use function count;

/**
 * Mail template list fetched — Final, admin-side grid projection
 * (Wave 9, goMailTemplateList).
 *
 *   GetMailTemplateListInput → MailTemplateListFetched  (Direct, safe read)
 *
 * AUTHZ — admin firewall (Wave 4 contract):
 *   AdminSession::$adminId === null → UnauthorizedAdminAccess
 *
 * Public surface — full MailTemplateEntity projection (no
 * passwordHash-grade secrets in this table, so the subject text is
 * safe to expose to an authenticated admin). The grid links to the
 * per-template UPDATE affordance (doUpdateMailTemplate, Wave 8ε).
 */
final readonly class MailTemplateListFetched
{
    /** @var list<array{mailTemplateId: int, mailTemplateName: string, fileName: string, mailSubject: string, isDeletable: bool}> */
    public array $mailTemplates;

    public int $count;

    public function __construct(
        #[Inject] AdminSession $adminSession,
        #[Inject] MailTemplateStorageInterface $mailTemplateStorage,
    ) {
        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $rows = $mailTemplateStorage->list();

        $this->mailTemplates = array_map(
            static fn (MailTemplateEntity $t): array => [
                'mailTemplateId' => $t->mailTemplateId,
                'mailTemplateName' => $t->mailTemplateName,
                'fileName' => $t->fileName,
                'mailSubject' => $t->subject,
                'isDeletable' => $t->deletable === 1,
            ],
            $rows,
        );
        $this->count = count($rows);
    }
}
