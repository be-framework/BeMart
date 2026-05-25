<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Fake\Service;

use MyVendor\BeMart\Be\Reason\Service\CsrfToken;
use Override;

use function hash_equals;
use function is_string;

/**
 * In-memory CSRF token fake.
 *
 * Holds a fixed reference token for the lifetime of the injector. Tests pass
 * {@see TOKEN} as the `csrfToken` request body field; mismatches are rejected.
 */
final readonly class FakeCsrfToken extends CsrfToken
{
    /** Reference token tests submit as the `csrfToken` request field. */
    public const TOKEN = 'fake-csrf-token-bemart-2026';

    /** @param non-empty-string $token */
    public function __construct(string $token = self::TOKEN)
    {
        parent::__construct($token);
    }

    #[Override]
    public function isValid(string|null $token): bool
    {
        if (! is_string($token) || $token === '') {
            return false;
        }

        return hash_equals($this->token, $token);
    }
}
