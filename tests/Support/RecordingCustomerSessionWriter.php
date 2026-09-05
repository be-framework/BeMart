<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Support;

use MyVendor\BeMart\Auth\CustomerSessionWriterInterface;
use Override;

/** Test double for the customer session port a Resource authenticates through and clears. */
final class RecordingCustomerSessionWriter implements CustomerSessionWriterInterface
{
    public string|null $authenticated = null;
    public bool $cleared = false;

    #[Override]
    public function authenticate(string $customerId): void
    {
        $this->authenticated = $customerId;
    }

    #[Override]
    public function clear(): void
    {
        $this->cleared = true;
    }
}
