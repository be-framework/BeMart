<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Auth;

interface CartSessionPrefixInterface
{
    public function prefix(): string|null;
}
