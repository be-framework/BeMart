<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Auth;

use Override;

final class NoopAdminSessionWriter implements AdminSessionWriterInterface
{
    #[Override]
    public function clear(): void
    {
    }
}
