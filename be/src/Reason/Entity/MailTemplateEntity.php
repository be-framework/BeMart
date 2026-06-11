<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Entity;

/**
 * MailTemplate entity — projection of EC-CUBE 4.3 dtb_mail_template.
 *
 * Each row is one mail template (order-confirm, register-thanks,
 * password-reset, …) addressed by an integer id. The admin screen
 * lists every template and lets the operator edit the subject. The
 * file path (`file_name`) is fixed at creation time and not editable
 * post-create.
 *
 * 厳密移植 alignment: dtb_mail_template has NO body columns. EC-CUBE
 * 4.3 stores mail bodies as Twig files on disk — `file_name` is the
 * path to that template. `body` and `htmlBody` were BeMart-only
 * fields that drifted from the schema and have been dropped; the mail
 * body is the on-disk Twig file, not a database column.
 *
 * doUpdateMailTemplate (Wave 8) only updates the subject — the
 * migration scope does NOT yet cover the create-new-template flow.
 * The mailTemplateId is therefore required input and MUST match an
 * existing row.
 */
final readonly class MailTemplateEntity implements \Ray\MediaQuery\ToScalarInterface
{
    use MediaQueryJsonEntityTrait;

    public function __construct(
        public int $mailTemplateId,
        public string $mailTemplateName,
        public string $fileName,
        public string $subject,
        public int $deletable = 0,
    ) {
    }
}
