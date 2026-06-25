<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;

/**
 * Requested お届け日 — the delivery-date slot chosen on /shopping
 * (a plain Y-m-d candidate offered by deliveryOptions, or '' for 指定なし).
 * Presentation/transport value; type assertion only.
 */
final class DeliveryDate
{
    #[Validate]
    public function validate(string|null $deliveryDate): void
    {
        // Type assertion only — the candidate set is offered by deliveryOptions.
    }
}
