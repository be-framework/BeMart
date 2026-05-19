<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\LimitFormatException;

/**
 * Generic result-set size cap — Wave 5 (goCustomerList) and any future
 * list-projection that needs a "max rows" knob. Distinct from
 * {@see OrderLimit} which is specifically the mypage recent-orders cap;
 * `Limit` is the cross-cutting one for admin-side grid resources.
 *
 * Positive integer; 1—50 mirrors OrderLimit's bound so the admin grid
 * cannot be widened by a tampered request parameter beyond what
 * FakeCustomerStorage::search already caps at the storage layer.
 */
final class Limit
{
    #[Validate]
    public function validate(int $limit): void
    {
        if ($limit < 1 || $limit > 50) {
            throw new LimitFormatException();
        }
    }
}
