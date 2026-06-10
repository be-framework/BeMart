<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Auth;

use Override;

final class NoopCustomerSessionWriter implements CustomerSessionWriterInterface
{
    #[Override]
    public function authenticate(string $customerId): void
    {
    }

    #[Override]
    public function clear(): void
    {
    }
}
