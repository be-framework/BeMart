<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

use DateTimeImmutable;

interface ClockInterface
{
    public function now(): DateTimeImmutable;
}
