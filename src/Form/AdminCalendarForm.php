<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Form;

use Override;
use Ray\WebFormModule\AbstractForm;

/**
 * EC-CUBE 定休日カレンダー設定フォーム — Setting/Shop Tier-2.
 *
 * PORT of EC-CUBE 4.3 `Form/Type/Admin/CalendarType` leaf fields.
 * The page is a renderer-only skeleton until BeMart gains a calendar
 * master transition/storage.
 */
final class AdminCalendarForm extends AbstractForm
{
    #[Override]
    public function init(): void
    {
        $this->setField('title', 'text')
            ->setAttribs(['id' => 'calendar_title', 'class' => 'form-control']);
        $this->setField('holiday', 'date')
            ->setAttribs([
                'id' => 'calendar_holiday',
                'class' => 'datetimepicker-input form-control',
                'data-target' => '#calendar_create_date_start',
                'data-toggle' => 'datetimepicker',
            ]);

        $this->filter->validate('title')->isNotBlank();
        $this->filter->validate('holiday')->isNotBlank();
    }

    /** @param array{title: string, holiday: string} $values */
    public function fillValues(array $values): void
    {
        $this->fill($values);
    }
}
