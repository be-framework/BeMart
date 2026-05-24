<?php

declare(strict_types=1);

namespace MyVendor\BeMart;

/** @internal */
final readonly class BootstrapRequest
{
    /** @param array<string, mixed> $params */
    public function __construct(
        public string $method,
        public string $target,
        public string $path,
        public array $params,
    ) {
    }
}
