<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\AdminPasswordChanged;

/**
 * Input for `doChangePassword` — the logged-in admin changes their own
 * password (Hard ActionRedirect completion, 認証/credential 系).
 *
 *   ChangeAdminPasswordInput → AdminPasswordChanged   (Direct, unsafe, admin AUTHZ)
 *
 * Derived from EC-CUBE's `admin_change_password` route
 * (`ChangePasswordController`). EC-CUBE verifies the current password,
 * applies the RepeatedType-confirmed new password through the Symfony
 * password hasher, and re-hashes the `dtb_member` row. The new password
 * targets the *current session admin* — there is no target selector, so
 * this Input carries only the three form fields; the target adminId is
 * read from {@see \MyVendor\BeMart\Be\Reason\Service\AdminSession} in the
 * Final.
 *
 * Mass-assignment safety: only the password-change fields are accepted.
 * loginId / authority / work cannot be reached through this Input.
 */
#[Be(AdminPasswordChanged::class)]
final readonly class ChangeAdminPasswordInput
{
    /**
     * @psalm-taint-source input $currentPassword
     * @psalm-taint-source input $changePasswordFirst
     * @psalm-taint-source input $changePasswordSecond
     */
    public function __construct(
        public string $currentPassword,
        public string $changePasswordFirst,
        public string $changePasswordSecond,
    ) {
    }
}
