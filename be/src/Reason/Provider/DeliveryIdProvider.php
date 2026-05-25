<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Provider;

use MyVendor\BeMart\Be\Reason\Query\DeliveryIdQueryInterface;
use Override;
use Ray\Di\ProviderInterface;

/** @implements ProviderInterface<string> */
final readonly class DeliveryIdProvider implements ProviderInterface
{
    public function __construct(
        private DeliveryIdQueryInterface $ids,
    ) {
    }

    #[Override]
    public function get(): string
    {
        return $this->ids->next()->value;
    }
}
