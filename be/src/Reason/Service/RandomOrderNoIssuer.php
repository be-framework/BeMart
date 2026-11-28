<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

use Override;

use function bin2hex;
use function random_bytes;

final class RandomOrderNoIssuer implements OrderNoIssuerInterface
{
    /** @return non-empty-string */
    #[Override]
    public function issue(): string
    {
        return bin2hex(random_bytes(16));
    }
}
