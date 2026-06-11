<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;

/**
 * AuthorityRoles — EC-CUBE admin authority URL-deny rule form rows.
 *
 * The HTML form posts `AuthorityRoles[*][Authority]` and
 * `AuthorityRoles[*][deny_url]`. Row-level normalization happens in the
 * update Final because EC-CUBE includes empty client-side rows.
 */
final class AuthorityRoles
{
    #[Validate]
    public function validate(array $AuthorityRoles): void
    {
    }
}
