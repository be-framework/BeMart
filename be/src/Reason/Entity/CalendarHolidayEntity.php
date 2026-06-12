<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Entity;

/**
 * Admin-side holiday calendar row, projected from EC-CUBE dtb_calendar.
 */
final readonly class CalendarHolidayEntity implements \Ray\MediaQuery\ToScalarInterface
{
    use MediaQueryJsonEntityTrait;

    public function __construct(
        public string $calendarId,
        public string|null $title,
        public string $holiday,
    ) {
    }
}
