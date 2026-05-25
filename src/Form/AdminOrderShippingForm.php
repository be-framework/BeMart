<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Form;

use Override;
use Ray\WebFormModule\AbstractForm;

/**
 * EC-CUBE 出荷登録 / 配送先編集フォーム — Order Tier-2.
 *
 * PORT of the leaf fields of EC-CUBE 4.3 `Form/Type/Admin/ShippingType`
 * used by `admin/Order/shipping.twig` — the ~709-line per-order
 * shipping editor. The Be domain edits the order's shipping target via
 * {@see \MyVendor\BeMart\Be\Input\AdminUpdateShippingAddressInput}
 * (name01 / name02 / postalCode / pref / addr01 / addr02 /
 * phoneNumber); this form ports exactly those editable fields. The
 * EC-CUBE editor's per-shipment delivery-slot / delivery-date / item
 * collection is Phase 2 scope — dropped as an enumerated residual.
 *
 * VALIDATION AUTHORITY STAYS WITH the Be domain — this form is a
 * field-definition + renderer only.
 */
final class AdminOrderShippingForm extends AbstractForm
{
    #[Override]
    public function init(): void
    {
        $this->setField('name01', 'text')
            ->setAttribs(['id' => 'shipping_name_name01', 'class' => 'form-control', 'placeholder' => '姓']);
        $this->setField('name02', 'text')
            ->setAttribs(['id' => 'shipping_name_name02', 'class' => 'form-control', 'placeholder' => '名']);
        $this->setField('postal_code', 'text')
            ->setAttribs(['id' => 'shipping_postal_code', 'class' => 'form-control']);
        // pref is an EC-CUBE master-data <select> (mtb_pref); the option
        // set is Doctrine data the resource body does not carry, so the
        // control renders with no <option>s — enumerated residual.
        $this->setField('pref', 'select')
            ->setAttribs(['id' => 'shipping_address_pref', 'class' => 'form-select'])
            ->setOptions([]);
        $this->setField('addr01', 'text')
            ->setAttribs(['id' => 'shipping_address_addr01', 'class' => 'form-control']);
        $this->setField('addr02', 'text')
            ->setAttribs(['id' => 'shipping_address_addr02', 'class' => 'form-control']);
        $this->setField('phone_number', 'text')
            ->setAttribs(['id' => 'shipping_phone_number', 'class' => 'form-control']);

        // Non-authoritative structural checks only — authority is the Be domain.
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
