<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\OffsetFormatException;

/**
 * Pagination cursor — used together with a page-size cap to walk past the
 * first page of a list projection (goOrderHistory is the first consumer).
 *
 * Non-negative integer; 0—10000. The upper bound is a safety rail rather
 * than a per-customer limit: even an unusually busy account is highly
 * unlikely to need a deeper cursor than this, and capping it here keeps
 * malicious `offset=PHP_INT_MAX` style probes from reaching the storage
 * layer.
 */
final class Offset
{
    #[Validate]
    public function validate(int $offset): void
    {
        if ($offset < 0 || $offset > 10000) {
            throw new OffsetFormatException();
        }
    }
}
