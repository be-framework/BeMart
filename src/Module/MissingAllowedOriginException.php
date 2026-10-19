<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Module;

use RuntimeException;

use function sprintf;

final class MissingAllowedOriginException extends RuntimeException
{
    public function __construct(string $env)
    {
        parent::__construct(sprintf(
            '%s is not set. The prod context refuses to serve with the same-origin gate down; set it to the shop origin, e.g. https://shop.example.',
            $env,
        ));
    }
}
