<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Fake\Service;

use MyVendor\BeMart\Be\Reason\Service\ClientIpInterface;
use Override;

/** Fixed client IP so audit rows are byte-comparable in tests and demos. */
final class FakeClientIp implements ClientIpInterface
{
    public const string ADDRESS = '203.0.113.7';

    #[Override]
    public function address(): string
    {
        return self::ADDRESS;
    }
}
