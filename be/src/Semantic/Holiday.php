<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;

/**
 * Calendar holiday date — EC-CUBE dtb_calendar.holiday.
 */
final class Holiday
{
    #[Validate]
    public function validate(string|null $holiday): void
    {
        // Type assertion only — form and SQL date conversion define the boundary.
    }
}
