<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Fake\Service;

use DateTimeImmutable;
use MyVendor\BeMart\Be\Reason\Service\ClockInterface;
use Override;

final class FakeClock implements ClockInterface
{
    public const string NOW = '2026-05-15T12:34:56+09:00';

    #[Override]
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable(self::NOW);
    }
}
