<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Fake\Service;

use MyVendor\BeMart\Be\Reason\Service\ClientIpInterface;
use Override;

/**
 * Client IP the test rewrites between attempts, to vary the throttle
 * key while the attempts share one in-memory audit store.
 */
final class MutableClientIp implements ClientIpInterface
{
    public function __construct(public string $address)
    {
    }

    #[Override]
    public function address(): string
    {
        return $this->address;
    }
}
