<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Entity;

/**
 * MailTemplate entity — projection of EC-CUBE 4.3 dtb_mail_template.
 *
 * Each row is one mail template (order-confirm, register-thanks,
 * password-reset, …) addressed by an integer id. The admin screen
 * lists every template and lets the operator edit subject + body +
 * htmlBody. The file path (`file_name`) is fixed at creation time and
 * not editable post-create.
 *
 * doUpdateMailTemplate (Wave 8) only updates subject / body /
 * htmlBody — the migration scope does NOT yet cover the create-new-
 * template flow. The mailTemplateId is therefore required input and
 * MUST match an existing row.
 */
final readonly class MailTemplateEntity
{
    public function __construct(
        public int $mailTemplateId,
        public string $mailTemplateName,
        public string $fileName,
        public string $subject,
        public string $body,
        public string|null $htmlBody = null,
    ) {
    }
}
