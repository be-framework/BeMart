<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Auth;

use Override;

final class NoopCartSessionPrefix implements CartSessionPrefixInterface
{
    #[Override]
    public function prefix(): string|null
    {
        return null;
    }
}
