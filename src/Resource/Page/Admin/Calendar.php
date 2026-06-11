<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin;

use BEAR\ApiDoc\Annotation\Alps;
use MyVendor\BeMart\Annotation\CsrfProtected;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Support\Resource\MutationResponseInterface;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Be\Final\AdminCalendarFetched;
use MyVendor\BeMart\Be\Final\CalendarHolidayCreated;
use MyVendor\BeMart\Be\Final\CalendarHolidayDeleted;
use MyVendor\BeMart\Be\Final\CalendarHolidayUpdated;
use MyVendor\BeMart\Be\Input\CreateCalendarHolidayInput;
use MyVendor\BeMart\Be\Input\DeleteCalendarHolidayInput;
use MyVendor\BeMart\Be\Input\GetAdminCalendarInput;
use MyVendor\BeMart\Be\Input\UpdateCalendarHolidayInput;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Service\CsrfToken;
use MyVendor\BeMart\Form\AdminCalendarForm;
use Ray\WebFormModule\FormFactory;
use BEAR\Resource\Annotation\JsonSchema;

use function assert;

/**
 * EC-CUBE 定休日カレンダー設定 — Setting/Shop Tier-2.
 *
 * Renderer and action surface for `Setting/Shop/calendar.twig`.
 */
class Calendar extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
        private readonly AdminSession $adminSession,
        private readonly CsrfToken $csrf,
        private readonly FormFactory $formFactory,
        private readonly MutationResponseInterface $mutationResponse,
    ) {
    }

    /** ALPS `goCalendar` に対応する GET 操作。 */
    #[Alps('goCalendar')]
    #[JsonSchema(schema: 'get-admin-calendar.json')]
    #[Link(rel: 'doCreateCalendarHoliday', href: 'page://self/admin/calendar', method: 'post')]
    #[Link(rel: 'doUpdateCalendar', href: 'page://self/admin/calendar', method: 'post')]
    #[Link(rel: 'doDeleteCalendarHoliday', href: 'page://self/admin/calendar', method: 'delete')]
    public function onGet(): static
    {
        if ($this->adminSession->adminId === null) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        $final = ($this->becoming)(new GetAdminCalendarInput());

        assert($final instanceof AdminCalendarFetched);

        $this->code = Code::OK;
        $this->body = $this->calendarBody($final->calendars);

        return $this;
    }

    /**
     * EC-CUBE doUpdateCalendar / doCreateCalendarHoliday.
     *
     * @psalm-taint-source input $operation
     * @psalm-taint-source input $title
     * @psalm-taint-source input $holiday
     * @psalm-taint-source input $calendarId
     */
    #[Alps('doUpdateCalendar')]
    #[JsonSchema(schema: 'post-admin-calendar.json', params: 'post-admin-calendar.param.json')]
    #[Link(rel: 'goCalendar', href: 'page://self/admin/calendar')]
    #[CsrfProtected]
    public function onPost(
        string $operation = 'update',
        string $title = '',
        string $holiday = '',
        string|int|null $calendarId = null,
    ): static {
        if ($this->adminSession->adminId === null) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        $isCreate = $operation === 'create';
        if ($isCreate) {
            $final = ($this->becoming)(new CreateCalendarHolidayInput(
                title: $title,
                holiday: $holiday,
            ));
            assert($final instanceof CalendarHolidayCreated);
            $transitionId = 'doCreateCalendarHoliday';
        } else {
            $final = ($this->becoming)(new UpdateCalendarHolidayInput(
                calendarId: (string) $calendarId,
                title: $title,
                holiday: $holiday,
            ));
            assert($final instanceof CalendarHolidayUpdated);
            $transitionId = 'doUpdateCalendar';
        }

        ($this->mutationResponse)($this, $isCreate ? Code::CREATED : Code::OK);
        $this->headers['Location'] = '/admin/calendar';
        $this->body = [
            'transitionId' => $transitionId,
            'calendarId' => $final->calendarId,
            'title' => $final->title,
            'holiday' => $final->holiday,
            'message' => $isCreate ? '休日を作成しました。' : '休日を更新しました。',
        ];

        return $this;
    }

    /**
     * EC-CUBE doDeleteCalendarHoliday.
     *
     * @psalm-taint-source input $calendarId
     */
    #[Alps('doDeleteCalendarHoliday')]
    #[JsonSchema(schema: 'delete-admin-calendar.json', params: 'delete-admin-calendar.param.json')]
    #[Link(rel: 'goCalendar', href: 'page://self/admin/calendar')]
    #[CsrfProtected]
    public function onDelete(string|int|null $calendarId = null): static
    {
        if ($this->adminSession->adminId === null) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        $final = ($this->becoming)(new DeleteCalendarHolidayInput(calendarId: (string) $calendarId));

        assert($final instanceof CalendarHolidayDeleted);

        ($this->mutationResponse)($this, Code::OK);
        $this->headers['Location'] = '/admin/calendar';
        $this->body = [
            'transitionId' => 'doDeleteCalendarHoliday',
            'calendarId' => $final->calendarId,
            'message' => '休日を削除しました。',
        ];

        return $this;
    }

    /**
     * @param list<array{calendarId: string, title: string|null, holiday: string}> $calendars
     * @return array{
     *     form: AdminCalendarForm,
     *     calendars: list<array{id: string, title: string, holiday: string, form: AdminCalendarForm, hasError: false}>,
     *     errors: list<string>,
     *     csrfToken: string
     * }
     */
    private function calendarBody(array $calendars): array
    {
        $form = $this->formFactory->newInstance(AdminCalendarForm::class);
        assert($form instanceof AdminCalendarForm);
        $form->fillValues(['title' => '', 'holiday' => '']);

        $items = [];
        foreach ($calendars as $row) {
            $rowForm = $this->formFactory->newInstance(AdminCalendarForm::class);
            assert($rowForm instanceof AdminCalendarForm);
            $rowForm->fillValues([
                'title' => (string) ($row['title'] ?? ''),
                'holiday' => $row['holiday'],
            ]);

            $items[] = [
                'id' => $row['calendarId'],
                'title' => (string) ($row['title'] ?? ''),
                'holiday' => $row['holiday'],
                'form' => $rowForm,
                'hasError' => false,
            ];
        }

        return [
            'form' => $form,
            'calendars' => $items,
            'errors' => [],
            'csrfToken' => $this->csrf->token,
        ];
    }
}
