<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\MailTemplateNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Entity\MailTemplateEntity;
use MyVendor\BeMart\Be\Reason\Query\MailTemplateStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Mail template updated — Final, proof an admin edited a mail
 * template's subject / body / htmlBody.
 *
 *   UpdateMailTemplateInput → MailTemplateUpdated (Direct, idempotent)
 *
 * Failure ladder:
 *   1. No admin session     → UnauthorizedAdminAccessException  (403)
 *   2. Unknown id           → MailTemplateNotFoundException     (404)
 *
 * Idempotency: when the new (subject, body, htmlBody) tuple equals
 * the persisted values, the storage write is skipped and the Final
 * reports `changed=false`.
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
    public string $mailBody;
    public string|null $mailHtmlBody;
    public bool $changed;

    public function __construct(
        #[Input] int $mailTemplateId,
        #[Input] string $mailSubject,
        #[Input] string $mailBody,
        #[Input] string|null $mailHtmlBody,
        #[Inject] AdminSessionInterface $adminSession,
        #[Inject] MailTemplateStorageInterface $mailTemplateStorage,
    ) {
        if ($adminSession->adminId() === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $existing = $mailTemplateStorage->findById($mailTemplateId);
        if ($existing === null) {
            throw new MailTemplateNotFoundException();
        }

        $next = new MailTemplateEntity(
            mailTemplateId: $existing->mailTemplateId,
            mailTemplateName: $existing->mailTemplateName,
            fileName: $existing->fileName,
            subject: $mailSubject,
            body: $mailBody,
            htmlBody: $mailHtmlBody,
        );

        $changed = $existing->subject !== $next->subject
            || $existing->body !== $next->body
            || $existing->htmlBody !== $next->htmlBody;

        if ($changed) {
            $mailTemplateStorage->update($next);
        }

        $this->mailTemplateId = $next->mailTemplateId;
        $this->mailTemplateName = $next->mailTemplateName;
        $this->fileName = $next->fileName;
        $this->mailSubject = $next->subject;
        $this->mailBody = $next->body;
        $this->mailHtmlBody = $next->htmlBody;
        $this->changed = $changed;
    }
}
