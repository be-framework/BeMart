<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Auth;

interface CustomerSessionWriterInterface
{
    public function authenticate(string $customerId): void;

    public function clear(): void;
}
