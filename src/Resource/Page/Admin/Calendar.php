<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin;

use MyVendor\BeMart\Annotation\CsrfProtected;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Form\AdminCalendarForm;
use Ray\WebFormModule\FormFactory;

use function assert;

/**
 * EC-CUBE 定休日カレンダー設定 — Setting/Shop Tier-2.
 *
 * Thin renderer / action surface for `Setting/Shop/calendar.twig`.
 * BeMart has no holiday-calendar storage in this wave, so POST/DELETE
 * deliberately expose a concrete, CSRF-protected Resource surface that
 * proves the EC-CUBE aliases no longer fall back to ActionRedirect.
 */
class Calendar extends ResourceObject
{
    public function __construct(
        private readonly AdminSession $adminSession,
        private readonly FormFactory $formFactory,
    ) {
    }

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

        $form = $this->formFactory->newInstance(AdminCalendarForm::class);
        assert($form instanceof AdminCalendarForm);
        $form->fillValues(['title' => '', 'holiday' => '']);

        $newYear = $this->formFactory->newInstance(AdminCalendarForm::class);
        assert($newYear instanceof AdminCalendarForm);
        $newYear->fillValues(['title' => '元日', 'holiday' => '2026-01-01']);

        $this->code = Code::OK;
        $this->body = [
            'form' => $form,
            'calendars' => [
                [
                    'id' => 1,
                    'title' => '元日',
                    'holiday' => '2026-01-01',
                    'form' => $newYear,
                    'hasError' => false,
                ],
            ],
            'errors' => [],
        ];

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
    #[Link(rel: 'goCalendar', href: 'page://self/admin/calendar')]
    #[CsrfProtected]
    public function onPost(
        string $operation = 'update',
        string $title = '',
        string $holiday = '',
        int|null $calendarId = null,
    ): static {
        if ($this->adminSession->adminId === null) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        $isCreate = $operation === 'create';
        $this->code = $isCreate ? Code::CREATED : Code::OK;
        $this->body = [
            'transitionId' => $isCreate ? 'doCreateCalendarHoliday' : 'doUpdateCalendar',
            'calendarId' => $calendarId,
            'title' => $title,
            'holiday' => $holiday,
            'message' => $isCreate ? '休日作成Resourceへ到達しました。' : '営業日カレンダー更新Resourceへ到達しました。',
        ];

        return $this;
    }

    /**
     * EC-CUBE doDeleteCalendarHoliday.
     *
     * @psalm-taint-source input $calendarId
     */
    #[Link(rel: 'goCalendar', href: 'page://self/admin/calendar')]
    #[CsrfProtected]
    public function onDelete(int|null $calendarId = null): static
    {
        if ($this->adminSession->adminId === null) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        $this->code = Code::OK;
        $this->body = [
            'transitionId' => 'doDeleteCalendarHoliday',
            'calendarId' => $calendarId,
            'message' => '休日削除Resourceへ到達しました。',
        ];

        return $this;
    }
}
