<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Form;

use Override;
use Ray\WebFormModule\AbstractForm;

/**
 * EC-CUBE 受注メール送信フォーム — Order Tier-2.
 *
 * PORT of the leaf fields of EC-CUBE 4.3 `Form/Type/Admin/OrderMailType`
 * used by `admin/Order/mail.twig` — the back-office order-mail
 * composition screen. The Be domain re-sends the order-confirmation
 * mail via {@see \MyVendor\BeMart\Be\Input\AdminSendOrderMailInput}
 * (keyed by orderNo only — the Mailer interface takes the order
 * entity); the custom subject / header / body overrides EC-CUBE
 * surfaces are Phase 2 scope. This form renders the composition
 * fields so the page is faithful; the resource consumes only the
 * orderNo on POST.
 *
 * VALIDATION AUTHORITY STAYS WITH the Be domain — this form is a
 * field-definition + renderer only.
 */
final class AdminOrderMailForm extends AbstractForm
{
    /** @var array<int|string, string> — numeric-string keys coerce to int */
    private const TEMPLATE_OPTIONS = [
        '' => '選択してください',
        '1' => '受注完了メール',
        '2' => '入金確認メール',
        '3' => '発送完了メール',
    ];

    #[Override]
    public function init(): void
    {
        $this->setField('template', 'select')
            ->setAttribs(['id' => 'mail_template', 'class' => 'form-select'])
            ->setOptions(self::TEMPLATE_OPTIONS);
        $this->setField('mail_subject', 'text')
            ->setAttribs(['id' => 'mail_mail_subject', 'class' => 'form-control']);
        $this->setField('mail_header', 'textarea')
            ->setAttribs(['id' => 'mail_mail_header', 'class' => 'form-control', 'rows' => '6']);
        $this->setField('mail_footer', 'textarea')
            ->setAttribs(['id' => 'mail_mail_footer', 'class' => 'form-control', 'rows' => '6']);

        // Non-authoritative structural check only — authority is the Be domain.
        $this->filter->validate('mail_subject')->isNotBlank();
    }

    /**
     * @param array<string, scalar|null> $values
     */
    public function fillValues(array $values): void
    {
        $this->fill($values);
    }
}
