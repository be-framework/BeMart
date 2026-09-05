<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\CalendarHolidayEntity;
use Ray\MediaQuery\Annotation\DbQuery;

interface CalendarHolidayStorageInterface
{
    /** @return list<CalendarHolidayEntity> */
    #[DbQuery('tcalendar_holiday_list')]
    public function list(): array;

    #[DbQuery('tcalendar_holiday_get')]
    public function item(string $calendarId): CalendarHolidayEntity|null;

    #[DbQuery('tcalendar_holiday_put')]
    public function put(CalendarHolidayEntity $calendar): void;

    #[DbQuery('tcalendar_holiday_delete')]
    public function delete(string $calendarId): void;
}
