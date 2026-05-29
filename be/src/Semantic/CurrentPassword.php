<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;

/**
 * CurrentPassword — the admin's existing plaintext password, submitted
 * on the password-change form for re-authentication.
 *
 * No format constraint is asserted here: correctness is a credential
 * check, not a syntax check, so {@see \MyVendor\BeMart\Be\Final\AdminPasswordChanged}
 * verifies it against the stored hash (a wrong value is an
 * authentication failure, not a malformed field). This Semantic exists
 * so the variable is named in the ontology and stored only long enough
 * to hand to the hasher.
 */
final class CurrentPassword
{
    #[Validate]
    public function validate(string $currentPassword): void
    {
    }
}
