<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

use DateTimeImmutable;
use Override;

final class SystemClock implements ClockInterface
{
    #[Override]
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable();
    }
}
