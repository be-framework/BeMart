<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Fake\Service;

use Override;
use Ray\Csrf\CsrfTokenInterface;

use function hash_equals;

/**
 * In-memory CSRF token fake.
 *
 * Holds a fixed reference token for the lifetime of the injector. Tests pass
 * {@see TOKEN} as the `csrfToken` request body field; mismatches are rejected.
 */
final readonly class FakeCsrfToken implements CsrfTokenInterface
{
    /** @var non-empty-string Reference token tests submit as the `csrfToken` request field. */
    public const TOKEN = 'fake-csrf-token-bemart-2026';

    /** @param non-empty-string $token */
    public function __construct(private string $token = self::TOKEN)
    {
    }

    /** @return non-empty-string */
    #[Override]
    public function issue(): string
    {
        return $this->token;
    }

    #[Override]
    public function verify(string $candidate): bool
    {
        if ($candidate === '') {
            return false;
        }

        return hash_equals($this->token, $candidate);
    }

    #[Override]
    public function clear(): void
    {
    }
}
