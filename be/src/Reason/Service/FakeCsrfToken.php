<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

use Override;

use function hash_equals;
use function is_string;

/**
 * In-memory CSRF token fake.
 *
 * Holds a fixed reference token for the lifetime of the injector. Tests
 * pass {@see TOKEN} as the `csrfToken` request body field; mismatches
 * (including missing/empty tokens) are rejected.
 *
 * Default AppModule binds `FakeCsrfToken` as a Singleton so Pilot 2-5
 * resource tests can rely on the same reference value across the suite.
 * Tests that need a custom reference (e.g. multi-token scenarios) can
 * override the binding with `new FakeCsrfToken('other')`.
 *
 * The fake DELIBERATELY uses `hash_equals` instead of `===`. Production
 * adapters must do the same, and exercising it in the fake means a
 * mistaken `===` would not silently make tests pass while leaving the
 * prod adapter inconsistent.
 */
final class FakeCsrfToken implements CsrfTokenInterface
{
    /**
     * Reference token tests submit as the `csrfToken` request field.
     * Public on purpose — tests reference this constant directly rather
     * than hard-coding the string in multiple places.
     */
    public const TOKEN = 'fake-csrf-token-bemart-2026';

    public function __construct(
        private readonly string $referenceToken = self::TOKEN,
    ) {
    }

    #[Override]
    public function isValid(string|null $token): bool
    {
        if (! is_string($token) || $token === '') {
            return false;
        }

        return hash_equals($this->referenceToken, $token);
    }
}
