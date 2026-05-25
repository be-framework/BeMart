<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\MailTemplateListFetched;

/**
 * Input for goMailTemplateList — admin lists every mail template
 * (Wave 9).
 *
 *   GetMailTemplateListInput → MailTemplateListFetched  (Direct, safe read)
 *
 * Pair of the Wave 8ε {@see UpdateMailTemplateInput} write side. Same
 * MailTemplateStorageInterface but uses `list()` instead of `findById()`.
 * No filter / paging — dtb_mail_template carries a small set of seeded
 * notification templates (order-confirm, register-thanks, …), the admin
 * grid displays all of them.
 *
 * AUTHZ in the Final (AdminSession).
 *
 * @link https://schema.org/SearchAction
 */
#[Be(MailTemplateListFetched::class)]
final readonly class GetMailTemplateListInput
{
    public function __construct()
    {
    }
}
