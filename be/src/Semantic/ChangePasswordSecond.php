<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;

/**
 * ChangePasswordSecond — the confirmation copy of the new password on
 * the admin password-change form.
 *
 * Equality against {@see ChangePasswordFirst} is a cross-field concern
 * enforced in {@see \MyVendor\BeMart\Be\Final\AdminPasswordChanged}. This
 * Semantic names the variable in the ontology.
 */
final class ChangePasswordSecond
{
    #[Validate]
    public function validate(string $changePasswordSecond): void
    {
    }
}
