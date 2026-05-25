<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Form;

use Override;
use Ray\WebFormModule\AbstractForm;

/**
 * EC-CUBE お届け先編集フォーム（管理画面）— Ray.WebFormModule.
 *
 * PORT of EC-CUBE 4.3 `Form/Type/Admin/CustomerAddressType` + the
 * `admin/Customer/delivery_edit.twig` `form_widget` calls. EC-CUBE
 * renders these inputs through the Symfony FormView; BeMart renders
 * them through Ray.WebFormModule — the same recipe as the sibling
 * {@see AdminCustomerForm} (the customer-profile editor).
 *
 * Flat field names — EC-CUBE's `CustomerAddressType` nests fields under
 * compound types (`form.name.name01`, `form.address.pref`), but the
 * rendered `<input>` ids reproduce the FormView ids the
 * `admin_customer_address` block prefix produces.
 *
 * VALIDATION AUTHORITY STAYS WITH the Be domain — this form is a
 * field-definition + renderer only. BeMart has no ALPS transition for
 * persisting a customer address in this wave, so the page is a thin
 * GET renderer and the form carries no domain-error bridge.
 */
final class AdminCustomerDeliveryForm extends AbstractForm
{
    #[Override]
    public function init(): void
    {
        // お名前 / お名前(カナ) — NameType / KanaType leaf pairs.
        $this->setField('name01', 'text')
            ->setAttribs(['id' => 'admin_customer_address_name_name01', 'class' => 'form-control']);
        $this->setField('name02', 'text')
            ->setAttribs(['id' => 'admin_customer_address_name_name02', 'class' => 'form-control']);
        $this->setField('kana01', 'text')
            ->setAttribs(['id' => 'admin_customer_address_kana_kana01', 'class' => 'form-control']);
        $this->setField('kana02', 'text')
            ->setAttribs(['id' => 'admin_customer_address_kana_kana02', 'class' => 'form-control']);

        // 会社名 (optional).
        $this->setField('company_name', 'text')
            ->setAttribs(['id' => 'admin_customer_address_company_name', 'class' => 'form-control']);

        // 住所 — postal code + prefecture select + address lines.
        $this->setField('postal_code', 'text')
            ->setAttribs(['id' => 'admin_customer_address_postal_code', 'class' => 'form-control']);
        // pref is an EC-CUBE master-data <select> (mtb_pref); the option
        // set is Doctrine data the resource body does not carry, so the
        // control renders with no <option>s — same residual as AdminCustomerForm.
        $this->setField('pref', 'select')
            ->setAttribs(['id' => 'admin_customer_address_address_pref', 'class' => 'form-select'])
            ->setOptions([]);
        $this->setField('addr01', 'text')
            ->setAttribs(['id' => 'admin_customer_address_address_addr01', 'class' => 'form-control']);
        $this->setField('addr02', 'text')
            ->setAttribs(['id' => 'admin_customer_address_address_addr02', 'class' => 'form-control']);

        // 電話番号.
        $this->setField('phone_number', 'text')
            ->setAttribs(['id' => 'admin_customer_address_phone_number', 'class' => 'form-control']);

        // NON-AUTHORITATIVE structural checks only — authority is the Be domain.
        $this->filter->validate('name01')->isNotBlank();
        $this->filter->validate('name02')->isNotBlank();
    }

    /**
     * @param array<string, scalar|null> $values
     */
    public function fillValues(array $values): void
    {
        $this->fill($values);
    }
}
