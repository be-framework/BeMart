<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Provider;

use MyVendor\BeMart\Be\Reason\Query\AdminIdQueryInterface;
use Override;
use Ray\Di\ProviderInterface;

/** @implements ProviderInterface<string> */
final readonly class AdminIdProvider implements ProviderInterface
{
    public function __construct(
        private AdminIdQueryInterface $ids,
    ) {
    }

    #[Override]
    public function get(): string
    {
        return $this->ids->next()->value;
    }
}
