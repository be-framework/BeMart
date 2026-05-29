<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

/**
 * Two-factor (TOTP) authentication boundary.
 *
 * EC-CUBE 4.3 stores a per-member TOTP secret on `dtb_member`
 * (`two_factor_auth_secret`) and verifies the device token with
 * `robthree/twofactorauth`. BeMart keeps that secret store + the RFC 6238
 * arithmetic behind this boundary so the Be Finals
 * ({@see \MyVendor\BeMart\Be\Final\TwoFactorAuthVerified} /
 * {@see \MyVendor\BeMart\Be\Final\TwoFactorAuthConfigured}) depend only on
 * an interface — the credential side-effect (secret persistence, token
 * arithmetic) is an adapter concern.
 */
interface TwoFactorAuthInterface
{
    /** Generate a fresh base32 TOTP secret (device-setup screen). */
    public function generateSecret(): string;

    /** Register/replace the TOTP secret for an admin (device setup). */
    public function enable(string $loginId, string $secret): void;

    /** Whether the admin already has a TOTP device configured. */
    public function isEnabled(string $loginId): bool;

    /**
     * Verify a submitted device token against the admin's stored secret.
     * Returns false on any mismatch (incl. unknown admin / no secret) —
     * callers MUST NOT branch on a more granular reason (timing-safety,
     * same contract as {@see PasswordHasherInterface::verify}).
     */
    public function verify(string $loginId, string $token): bool;
}
