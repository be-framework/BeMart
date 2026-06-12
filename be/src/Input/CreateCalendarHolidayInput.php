<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\CalendarHolidayCreated;

#[Be(CalendarHolidayCreated::class)]
final readonly class CreateCalendarHolidayInput
{
    /**
     * @psalm-taint-source input $title
     * @psalm-taint-source input $holiday
     */
    public function __construct(
        public string $title,
        public string $holiday,
    ) {
    }
}
