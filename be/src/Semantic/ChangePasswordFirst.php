<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;

/**
 * ChangePasswordFirst — the new plaintext password on the admin
 * password-change form.
 *
 * The length policy (8–32) and the confirmation match against
 * {@see ChangePasswordSecond} are cross-/post-field concerns enforced in
 * {@see \MyVendor\BeMart\Be\Final\AdminPasswordChanged}, where they form a
 * single observable validation ladder alongside the current-password
 * check. This Semantic names the variable in the ontology.
 */
final class ChangePasswordFirst
{
    #[Validate]
    public function validate(string $changePasswordFirst): void
    {
    }
}
