<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;

/**
 * Calendar holiday id — server-derived from EC-CUBE dtb_calendar.id.
 */
final class CalendarId
{
    #[Validate]
    public function validate(string|null $calendarId): void
    {
        // Type assertion only — storage and provider own allocation/existence.
    }
}
