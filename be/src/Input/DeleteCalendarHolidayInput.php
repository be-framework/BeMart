<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\CalendarHolidayDeleted;

#[Be(CalendarHolidayDeleted::class)]
final readonly class DeleteCalendarHolidayInput
{
    /**
     * @psalm-taint-source input $calendarId
     */
    public function __construct(
        public string $calendarId,
    ) {
    }
}
