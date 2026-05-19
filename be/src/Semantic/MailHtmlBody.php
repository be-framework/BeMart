<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\MailBodyFormatException;

use function mb_strlen;

/**
 * Mail HTML body — EC-CUBE 4.3 dtb_mail_template.html_body. Optional;
 * a null value means "send plain-text only".
 *
 * Same defensive 65,535 char cap as the plain-text {@see MailBody}.
 * Empty string IS allowed (distinct from MailBody where empty rejects)
 * because submitting an explicit empty html_body is how an admin clears
 * a previously-set HTML version.
 */
final class MailHtmlBody
{
    #[Validate]
    public function validate(string|null $mailHtmlBody): void
    {
        if ($mailHtmlBody === null) {
            return;
        }

        if (mb_strlen($mailHtmlBody) > 65535) {
            throw new MailBodyFormatException();
        }
    }
}
