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
 *   - SemanticVariableException        → 400 (subject format)
 *   - UnauthorizedAdminAccessException → 403 (no admin session)
 *   - MailTemplateNotFoundException    → 404 (unknown mailTemplateId)
 *
 * The migration scope only covers UPDATE of the subject. Creating a
 * new template (which requires setting the underlying file_name) is
 * Phase 2; mailTemplateName + fileName are therefore NOT in this
 * Input — they live on the existing row and are preserved by the
 * storage update.
 *
 * 厳密移植 alignment: dtb_mail_template has NO body columns — EC-CUBE
 * 4.3 stores mail bodies as on-disk Twig files. The former mailBody /
 * mailHtmlBody inputs were BeMart-only fields and have been dropped.
 *
 * Mass-assignment safety: only mailTemplateId + subject are accepted.
 * The fileName / mailTemplateName columns are NOT reachable through
 * this transition.
 *
 * @link https://schema.org/UpdateAction
 */
#[Be(MailTemplateUpdated::class)]
final readonly class UpdateMailTemplateInput
{
    /**
     * @psalm-taint-source input $mailTemplateId
     * @psalm-taint-source input $mailSubject
     */
    public function __construct(
        public int $mailTemplateId,
        public string $mailSubject,
    ) {
    }
}
