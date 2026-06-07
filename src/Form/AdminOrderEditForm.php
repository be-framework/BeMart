<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Form;

use Override;
use Ray\WebFormModule\AbstractForm;

/**
 * EC-CUBE 受注編集フォーム — Order Tier-2.
 *
 * PORT of the leaf fields of EC-CUBE 4.3 `Form/Type/Admin/OrderType`
 * used by `admin/Order/edit.twig` — the multi-panel order editor. The
 * full EC-CUBE editor wires a Customer panel, a Shipping collection,
 * an OrderItem collection and the price-recompute totals; the Be
 * projection ({@see \MyVendor\BeMart\Be\Final\AdminOrderFetched}) keeps
 * a flat header + a line-item snapshot, so this form ports the editable
 * header fields only — the order-level money knobs the admin can adjust
 * ({@see \MyVendor\BeMart\Be\Input\AdminUpdateOrderInput}: discount /
 * charge / usePoint) plus the customer-summary contact fields. Shipping
 * collection edits live on the sibling `shipping` page.
 */
final class AdminOrderEditForm extends AbstractForm
{
    #[Override]
    public function init(): void
    {
        $this->setField('name01', 'text')
            ->setAttribs(['id' => 'order_name_name01', 'class' => 'form-control', 'placeholder' => '姓']);
        $this->setField('name02', 'text')
            ->setAttribs(['id' => 'order_name_name02', 'class' => 'form-control', 'placeholder' => '名']);
        $this->setField('email', 'text')
            ->setAttribs(['id' => 'order_email', 'class' => 'form-control']);
        $this->setField('discount', 'text')
            ->setAttribs(['id' => 'order_discount', 'class' => 'form-control']);
        $this->setField('charge', 'text')
            ->setAttribs(['id' => 'order_charge', 'class' => 'form-control']);
        $this->setField('usePoint', 'text')
            ->setAttribs(['id' => 'order_use_point', 'class' => 'form-control']);
        $this->setField('message', 'textarea')
            ->setAttribs(['id' => 'order_message', 'class' => 'form-control', 'rows' => '6']);

        // Non-authoritative structural check only — authority is the Be domain.
        $this->filter->validate('name01')->isNotBlank();
    }

    /**
     * Pre-populates the editor with an order header projection.
     *
     * @param array<string, scalar|null> $values
     */
    public function fillValues(array $values): void
    {
        $this->fill($values);
    }
}
