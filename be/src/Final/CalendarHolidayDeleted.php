<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\CalendarHolidayNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Query\CalendarHolidayStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

final readonly class CalendarHolidayDeleted
{
    public string $calendarId;

    public function __construct(
        #[Input] string $calendarId,
        #[Inject] AdminSession $adminSession,
        #[Inject] CalendarHolidayStorageInterface $calendars,
    ) {
        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        if ($calendars->item($calendarId) === null) {
            throw new CalendarHolidayNotFoundException();
        }

        $calendars->delete($calendarId);
        $this->calendarId = $calendarId;
    }
}
