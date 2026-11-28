<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Fake\Service;

use DateTimeImmutable;
use MyVendor\BeMart\Be\Reason\Service\ClockInterface;
use Override;

final class FakeClock implements ClockInterface
{
    public const string NOW = '2026-04-01T10:00:00+09:00';

    #[Override]
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable(self::NOW);
    }
}
