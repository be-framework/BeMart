<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Auth;

interface AdminSessionWriterInterface
{
    public function clear(): void;
}
