<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Fake\Service;

use Override;
use BEAR\Csrf\CsrfTokenInterface;

/**
 * Test-default CSRF adapter.
 *
 * Use this for tests whose subject is not CSRF. Dedicated CSRF boundary
 * tests should bind {@see FakeCsrfToken} or the production adapter
 * explicitly.
 */
final readonly class NullCsrfToken implements CsrfTokenInterface
{
    public const TOKEN = FakeCsrfToken::TOKEN;

    #[Override]
    public function issue(): string
    {
        return self::TOKEN;
    }

    #[Override]
    public function verify(string $candidate): bool
    {
        return true;
    }

    #[Override]
    public function clear(): void
    {
    }
}
