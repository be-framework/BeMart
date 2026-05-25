<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\MailTemplateIdFormatException;

/**
 * Mail template id — EC-CUBE 4.3 dtb_mail_template.id, a positive
 * integer primary key. Static range check only; existence is the
 * Final's job (raises MailTemplateNotFoundException).
 */
final class MailTemplateId
{
    #[Validate]
    public function validate(int $mailTemplateId): void
    {
        if ($mailTemplateId < 1) {
            throw new MailTemplateIdFormatException();
        }
    }
}
