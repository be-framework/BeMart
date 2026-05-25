<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\PasswordResetCompleted;
use SensitiveParameter;

/**
 * Input for doResetPassword — Pilot 15, consumer of Pilot 14's token.
 *
 * Direct pattern (hello-world demo): Input → Final, no Being.
 *
 *   ResetPasswordInput → PasswordResetCompleted (Final)
 *
 * Semantic validation at Becoming time: ResetKey (URL-safe printable,
 * 16-128 chars) and Password (length 8-255). Token correctness +
 * expiry + single-use enforcement are the Final's job.
 *
 * Single-use: the Final calls
 * `PasswordResetTokenStorageInterface::delete()` immediately after a
 * successful reset, so a second attempt with the same key raises
 * `ResetKeyInvalidException` (merged "wrong / expired / used" branch
 * — same anti-enumeration design as Pilot 7's SecretKey).
 *
 * @link https://schema.org/ResetAction
 */
#[Be(PasswordResetCompleted::class)]
final readonly class ResetPasswordInput
{
    /**
     * Phase B Slice 9: both fields come from the password-reset form.
     *
     * @psalm-taint-source input $resetKey
     * @psalm-taint-source input $password
     */
    public function __construct(
        public string $resetKey,
        #[SensitiveParameter] public string $password,
    ) {
    }
}
