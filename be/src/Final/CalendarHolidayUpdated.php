<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\CalendarHolidayNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Entity\CalendarHolidayEntity;
use MyVendor\BeMart\Be\Reason\Query\CalendarHolidayStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

final readonly class CalendarHolidayUpdated
{
    public string $calendarId;
    public string $title;
    public string $holiday;

    public function __construct(
        #[Input] string $calendarId,
        #[Input] string $title,
        #[Input] string $holiday,
        #[Inject] AdminSession $adminSession,
        #[Inject] CalendarHolidayStorageInterface $calendars,
    ) {
        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        if ($calendars->item($calendarId) === null) {
            throw new CalendarHolidayNotFoundException();
        }

        $entity = new CalendarHolidayEntity(
            calendarId: $calendarId,
            title: $title,
            holiday: $holiday,
        );

        $calendars->put($entity);

        $this->calendarId = $entity->calendarId;
        $this->title = $entity->title ?? '';
        $this->holiday = $entity->holiday;
    }
}
