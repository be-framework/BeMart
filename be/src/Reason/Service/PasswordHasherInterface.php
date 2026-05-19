<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

interface PasswordHasherInterface
{
    public function hash(string $plaintext): string;

    /**
     * Verify a plaintext against a previously hashed value.
     *
     * Added in Pilot 6 (doLogin) so the authentication flow can reach
     * the bcrypt verify primitive through the same Reason that holds
     * the hash primitive. Returns false on any mismatch — callers MUST
     * NOT branch on more granular failure reasons (timing-safety).
     */
    public function verify(string $plaintext, string $hash): bool;
}
