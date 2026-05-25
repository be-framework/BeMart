<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Form\AdminCalendarForm;
use Ray\WebFormModule\FormFactory;

use function assert;

/**
 * EC-CUBE 定休日カレンダー設定 — Setting/Shop Tier-2.
 *
 * Thin GET renderer for `Setting/Shop/calendar.twig`. BeMart has no
 * ALPS transition/storage for holiday-calendar master rows in this
 * wave, so the body exposes a small renderer seed only.
 */
class Calendar extends ResourceObject
{
    public function __construct(
        private readonly AdminSessionInterface $adminSession,
        private readonly FormFactory $formFactory,
    ) {
    }

    public function onGet(): static
    {
        if ($this->adminSession->adminId() === null) {
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
}
