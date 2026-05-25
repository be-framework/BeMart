<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\MailTemplateNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Entity\MailTemplateEntity;
use MyVendor\BeMart\Be\Reason\Query\MailTemplateStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Mail template updated — Final, proof an admin edited a mail
 * template's subject.
 *
 *   UpdateMailTemplateInput → MailTemplateUpdated (Direct, idempotent)
 *
 * Failure ladder:
 *   1. No admin session     → UnauthorizedAdminAccessException  (403)
 *   2. Unknown id           → MailTemplateNotFoundException     (404)
 *
 * Idempotency: when the new subject equals the persisted value, the
 * storage write is skipped and the Final reports `changed=false`.
 *
 * 厳密移植 alignment: dtb_mail_template has NO body columns — EC-CUBE
 * 4.3 stores mail bodies as on-disk Twig files. The former mailBody /
 * mailHtmlBody fields were BeMart-only and have been dropped.
 *
 * Mass-assignment safety: mailTemplateName + fileName are NOT in the
 * Input, so this transition cannot rename the template or rebind it
 * to a different Twig file. Those fields are Phase 2 scope.
 */
final readonly class MailTemplateUpdated
{
    public int $mailTemplateId;
    public string $mailTemplateName;
    public string $fileName;
    public string $mailSubject;
    public bool $changed;

    public function __construct(
        #[Input] int $mailTemplateId,
        #[Input] string $mailSubject,
        #[Inject] AdminSession $adminSession,
        #[Inject] MailTemplateStorageInterface $mailTemplateStorage,
    ) {
        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $existing = $mailTemplateStorage->item($mailTemplateId);
        if ($existing === null) {
            throw new MailTemplateNotFoundException();
        }

        $next = new MailTemplateEntity(
            mailTemplateId: $existing->mailTemplateId,
            mailTemplateName: $existing->mailTemplateName,
            fileName: $existing->fileName,
            subject: $mailSubject,
        );

        $changed = $existing->subject !== $next->subject;

        if ($changed) {
            $mailTemplateStorage->update($next);
        }

        $this->mailTemplateId = $next->mailTemplateId;
        $this->mailTemplateName = $next->mailTemplateName;
        $this->fileName = $next->fileName;
        $this->mailSubject = $next->subject;
        $this->changed = $changed;
    }
}
