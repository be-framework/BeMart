<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\MailSubjectFormatException;

use function mb_strlen;
use function trim;

/**
 * Mail subject — EC-CUBE 4.3 dtb_mail_template.subject.
 *
 * Non-empty, <= 255 chars. RFC 5322 caps headers at 998 octets but
 * 255 is the practical maximum for human-readable subjects and is
 * what most MTAs / clients display without truncation.
 */
final class MailSubject
{
    #[Validate]
    public function validate(string $mailSubject): void
    {
        if (trim($mailSubject) === '' || mb_strlen($mailSubject) > 255) {
            throw new MailSubjectFormatException();
        }
    }
}
