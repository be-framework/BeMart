<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

interface CustomerIdGeneratorInterface
{
    public function generate(): string;
}
