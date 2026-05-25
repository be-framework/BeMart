<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

interface PasswordHasherInterface
{
    public function hash(string $plaintext): string;
}
