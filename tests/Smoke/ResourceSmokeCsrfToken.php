<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Smoke;

use Override;
use Ray\Csrf\CsrfTokenInterface;

final class ResourceSmokeCsrfToken implements CsrfTokenInterface
{
    /** @return non-empty-string */
    #[Override]
    public function issue(): string
    {
        return 'resource-smoke-csrf-token';
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
