<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Provider;

use Override;
use Ray\Di\ProviderInterface;

use function bin2hex;
use function random_bytes;

/** @implements ProviderInterface<non-empty-string> */
final readonly class OrderNoProvider implements ProviderInterface
{
    /** @return non-empty-string */
    #[Override]
    public function get(): string
    {
        return bin2hex(random_bytes(16));
    }
}
