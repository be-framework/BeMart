<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Smoke;

use Override;
use BEAR\Csrf\CsrfTokenInterface;

final readonly class ResourceSmokeCsrfToken implements CsrfTokenInterface
{
    private const TOKEN = 'resource-smoke-csrf-token';

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
