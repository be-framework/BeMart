<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Form;

use Override;
use Ray\WebFormModule\AbstractForm;

/**
 * EC-CUBE 商品規格フォーム — Product Tier-2.
 *
 * PORT of the leaf fields of EC-CUBE 4.3 `Form/Type/Admin/ProductClassType`
 * used by `admin/Product/product_class.twig` — the ~448-line product-class
 * matrix editor. The full EC-CUBE editor renders one row per
 * 規格1 × 規格2 cell, each carrying its own price / stock / stock-unlimited
 * / sale-type / delivery-date / shipping-charge controls. The Be domain
 * has no transition to read a product's ProductClass matrix (the
 * ProductClass join is Grade-C 厳密移植 scope), so this form ports the
 * per-row editable leaf fields once — the template repeats them per
 * matrix cell — and the editor renders a blank "新規規格" row.
 *
 * VALIDATION AUTHORITY STAYS WITH the Be domain — this form is a
 * field-definition + renderer only (see var/templates/README.md).
 */
final class AdminProductClassForm extends AbstractForm
{
    #[Override]
    public function init(): void
    {
        $this->setField('price02', 'text')
            ->setAttribs(['id' => 'product_class_price02', 'class' => 'form-control']);
        $this->setField('stock', 'text')
            ->setAttribs(['id' => 'product_class_stock', 'class' => 'form-control']);
        $this->setField('stock_unlimited', 'checkbox')
            ->setAttribs(['id' => 'product_class_stock_unlimited'])
            ->setOptions(['1' => '']);
        $this->setField('product_code', 'text')
            ->setAttribs(['id' => 'product_class_code', 'class' => 'form-control']);
        $this->setField('delivery_fee', 'text')
            ->setAttribs(['id' => 'product_class_delivery_fee', 'class' => 'form-control']);

        // Non-authoritative structural check only — authority is the Be domain.
        $this->filter->validate('price02')->isNotBlank();
    }

    /**
     * Pre-populates one matrix-row editor.
     *
     * @param array<string, scalar|null> $values
     */
    public function fillValues(array $values): void
    {
        $this->fill($values);
    }
}
