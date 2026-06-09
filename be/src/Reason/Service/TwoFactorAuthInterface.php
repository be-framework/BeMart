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

    /** Generate the current 6-digit device token for a candidate secret. */
    public function generateDeviceToken(string $secret): string;

    /**
     * Register/replace the TOTP secret for an admin (device setup).
     *
     * SECURITY CONTRACT: `$loginId` MUST be the identity established by the
     * current authentication step, never a raw client-supplied value. This
     * method overwrites any existing secret for `$loginId` with no ownership
     * check, so a caller that forwards an attacker-controlled `$loginId`
     * would let one actor overwrite another admin's 2FA device (lockout /
     * account takeover). The adapter that drives
     * {@see \MyVendor\BeMart\Be\Final\TwoFactorAuthConfigured} is responsible
     * for binding `$loginId` to the just-authenticated login before reaching
     * this transition.
     */
    public function enable(string $loginId, string $secret): void;

    /** Whether the admin already has a TOTP device configured. */
    public function isEnabled(string $loginId): bool;

    /**
     * Verify a submitted device token against a CANDIDATE secret that is
     * not (yet) persisted — used by device setup to confirm the first
     * code BEFORE committing the secret via {@see enable}, so a wrong
     * code never mutates stored credentials. Returns false on any
     * mismatch (timing-safety, same contract as {@see verify}).
     */
    public function verifySecret(string $secret, string $token): bool;

    /**
     * Verify a submitted device token against the admin's stored secret.
     * Returns false on any mismatch (incl. unknown admin / no secret) —
     * callers MUST NOT branch on a more granular reason (timing-safety,
     * same contract as {@see PasswordHasherInterface::verify}).
     */
    public function verify(string $loginId, string $token): bool;
}
