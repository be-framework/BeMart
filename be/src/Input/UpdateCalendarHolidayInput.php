<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\CalendarHolidayUpdated;

#[Be(CalendarHolidayUpdated::class)]
final readonly class UpdateCalendarHolidayInput
{
    /**
     * @psalm-taint-source input $calendarId
     * @psalm-taint-source input $title
     * @psalm-taint-source input $holiday
     */
    public function __construct(
        public string $calendarId,
        public string $title,
        public string $holiday,
    ) {
    }
}
