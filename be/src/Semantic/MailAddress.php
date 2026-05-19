<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\EmailFormatException;

use function mb_strlen;
use function str_contains;

/**
 * MailAddress — EC-CUBE dtb_member.mail_address. Same shape as
 * {@see Email} (customer-side) but a distinct Semantic because admin
 * field is named `mailAddress` and Be Semantic dispatch matches on
 * parameter name. Reuses {@see EmailFormatException} — the failure
 * mode and human-readable message are identical (just a "this field
 * is not a valid email").
 *
 * Static constraints only — RFC 5322 contains-`@` + length cap 254.
 *
 * The parameter is nullable to support partial-update transitions
 * (e.g. doUpdateMember leaves the mailAddress unchanged when null);
 * null is treated as "no validation needed".
 */
final class MailAddress
{
    #[Validate]
    public function validate(string|null $mailAddress): void
    {
        if ($mailAddress === null) {
            return;
        }

        if (! str_contains($mailAddress, '@') || mb_strlen($mailAddress) > 254 || mb_strlen($mailAddress) < 3) {
            throw new EmailFormatException();
        }
    }
}
