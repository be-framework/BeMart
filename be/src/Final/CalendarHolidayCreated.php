<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Entity\CalendarHolidayEntity;
use MyVendor\BeMart\Be\Reason\Provider\CalendarHolidayIdProvider;
use MyVendor\BeMart\Be\Reason\Query\CalendarHolidayStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

final readonly class CalendarHolidayCreated
{
    public string $calendarId;
    public string $title;
    public string $holiday;

    public function __construct(
        #[Input] string $title,
        #[Input] string $holiday,
        #[Inject] AdminSession $adminSession,
        #[Inject] CalendarHolidayStorageInterface $calendars,
        #[Inject] CalendarHolidayIdProvider $ids,
    ) {
        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $entity = new CalendarHolidayEntity(
            calendarId: $ids->get(),
            title: $title,
            holiday: $holiday,
        );

        $calendars->put($entity);

        $this->calendarId = $entity->calendarId;
        $this->title = $entity->title ?? '';
        $this->holiday = $entity->holiday;
    }
}
