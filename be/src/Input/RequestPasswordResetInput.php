<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\PasswordResetRequested;

/**
 * Input for doRequestPasswordReset — Pilot 14.
 *
 * Direct pattern.
 *
 *   RequestPasswordResetInput → PasswordResetRequested (Final)
 *
 * Anti-enumeration: the response is identical whether the email is
 * registered or not. The Final stores+mails when found; silently
 * returns "issued" when not.
 *
 * @link https://schema.org/ResetAction
 */
#[Be(PasswordResetRequested::class)]
final readonly class RequestPasswordResetInput
{
    /**
     * @psalm-taint-source input $email
     */
    public function __construct(
        public string $email,
    ) {
    }
}
