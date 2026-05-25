<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Form;

use Override;
use Ray\WebFormModule\AbstractForm;

/**
 * EC-CUBE 帳票出力フォーム — Order Tier-2.
 *
 * PORT of the leaf fields of EC-CUBE 4.3 `Form/Type/Admin/OrderPdfType`
 * used by `admin/Order/order_pdf.twig` — the delivery-note (納品書) PDF
 * options screen. The admin picks a title / greeting message / note
 * and a print date before the PDF is rendered. BeMart's PDF export
 * ({@see \MyVendor\BeMart\Resource\Page\Admin\Order\ExportOrderPdf}) is
 * a Phase 2 stub keyed by orderNo; this form ports the options panel so
 * the page is faithful — the resource consumes the orderNo only.
 *
 * VALIDATION AUTHORITY STAYS WITH the Be domain — this form is a
 * field-definition + renderer only.
 */
final class AdminOrderPdfForm extends AbstractForm
{
    #[Override]
    public function init(): void
    {
        $this->setField('title', 'text')
            ->setAttribs(['id' => 'order_pdf_title', 'class' => 'form-control']);
        $this->setField('message1', 'text')
            ->setAttribs(['id' => 'order_pdf_message1', 'class' => 'form-control']);
        $this->setField('message2', 'text')
            ->setAttribs(['id' => 'order_pdf_message2', 'class' => 'form-control']);
        $this->setField('message3', 'text')
            ->setAttribs(['id' => 'order_pdf_message3', 'class' => 'form-control']);
        $this->setField('note1', 'text')
            ->setAttribs(['id' => 'order_pdf_note1', 'class' => 'form-control']);
        $this->setField('note2', 'text')
            ->setAttribs(['id' => 'order_pdf_note2', 'class' => 'form-control']);
        $this->setField('note3', 'text')
            ->setAttribs(['id' => 'order_pdf_note3', 'class' => 'form-control']);
        $this->setField('issue_date', 'text')
            ->setAttribs(['id' => 'order_pdf_issue_date', 'class' => 'form-control', 'placeholder' => 'YYYY-MM-DD']);

        // Non-authoritative structural check only — authority is the Be domain.
        $this->filter->validate('title')->isNotBlank();
    }

    /**
     * @param array<string, scalar|null> $values
     */
    public function fillValues(array $values): void
    {
        $this->fill($values);
    }
}
