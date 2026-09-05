<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Entity\CalendarHolidayEntity;
use MyVendor\BeMart\Be\Reason\Query\CalendarHolidayStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use Ray\Di\Di\Inject;

use function array_map;

final readonly class AdminCalendarFetched
{
    /**
     * @var list<array{
     *     calendarId: string,
     *     title: string|null,
     *     holiday: string
     * }>
     */
    public array $calendars;

    public function __construct(
        #[Inject] AdminSession $adminSession,
        #[Inject] CalendarHolidayStorageInterface $calendars,
    ) {
        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $this->calendars = array_map(
            static fn (CalendarHolidayEntity $row): array => [
                'calendarId' => $row->calendarId,
                'title' => $row->title,
                'holiday' => $row->holiday,
            ],
            $calendars->list(),
        );
    }
}
