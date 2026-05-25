<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\MailTemplateUpdated;

/**
 * Input for doUpdateMailTemplate — admin edits a mail template.
 *
 *   UpdateMailTemplateInput → MailTemplateUpdated (Final — Direct, idempotent)
 *
 * Failure ladder:
 *   - SemanticVariableException        → 400 (subject/body format)
 *   - UnauthorizedAdminAccessException → 403 (no admin session)
 *   - MailTemplateNotFoundException    → 404 (unknown mailTemplateId)
 *
 * The migration scope only covers UPDATE of subject + body + htmlBody.
 * Creating a new template (which requires setting the underlying
 * file_name) is Phase 2; mailTemplateName + fileName are therefore
 * NOT in this Input — they live on the existing row and are preserved
 * by the storage update.
 *
 * Mass-assignment safety: only mailTemplateId + subject + body +
 * htmlBody are accepted. The fileName / mailTemplateName columns are
 * NOT reachable through this transition.
 *
 * @link https://schema.org/UpdateAction
 */
#[Be(MailTemplateUpdated::class)]
final readonly class UpdateMailTemplateInput
{
    /**
     * @psalm-taint-source input $mailTemplateId
     * @psalm-taint-source input $mailSubject
     * @psalm-taint-source input $mailBody
     * @psalm-taint-source input $mailHtmlBody
     */
    public function __construct(
        public int $mailTemplateId,
        public string $mailSubject,
        public string $mailBody,
        public string|null $mailHtmlBody = null,
    ) {
    }
}
