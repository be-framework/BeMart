<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Fake\Service;

use Override;
use Ray\Csrf\CsrfTokenInterface;

/**
 * Test-default CSRF token — accepts any candidate.
 *
 * Use this for tests whose subject is not CSRF. Dedicated CSRF boundary tests
 * should bind {@see FakeCsrfToken} or the production adapter explicitly.
 */
final class NullCsrfToken implements CsrfTokenInterface
{
    /** @var non-empty-string */
    public const TOKEN = FakeCsrfToken::TOKEN;

    /** @return non-empty-string */
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
