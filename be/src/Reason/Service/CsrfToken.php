<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

/**
 * CSRF token boundary for trusted form round-trips.
 *
 * The token rendered into forms is a value (`$token`), not a getter. The
 * validation operation remains behaviour because it compares submitted input
 * against the trusted request/session reference with timing-safe equality.
 */
abstract readonly class CsrfToken
{
    /** Trusted CSRF reference token for the current request/session. */
    public string $token;

    /** @param non-empty-string $token */
    public function __construct(string $token)
    {
        $this->token = $token;
    }

    /**
     * Returns true iff the supplied token matches the trusted reference for the
     * current request. Empty, null, mismatched, or out-of-session tokens always
     * return false. Comparison must be timing-safe (`hash_equals`).
     */
    abstract public function isValid(string|null $token): bool;
}
