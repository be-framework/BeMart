<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Form;

use Override;
use Ray\WebFormModule\AbstractForm;

/**
 * EC-CUBE 商品登録・編集フォーム — Product Tier-2.
 *
 * PORT of the leaf fields of EC-CUBE 4.3 `Form/Type/Admin/ProductType`
 * used by `admin/Product/product.twig` — the ~932-line multi-tab
 * product editor. The full EC-CUBE editor wires a product-image
 * collection, a category multi-select, a tag collection and the
 * class-category drag-drop matrix; the Be projection
 * ({@see \MyVendor\BeMart\Be\Final\AdminProductFetched}) keeps a flat
 * product header (the dtb_product columns the admin product-management
 * transitions exercise), so this form ports the editable header fields
 * only — name / price / stock / status / description / search word /
 * note. The class-category matrix edits live on the sibling
 * `product_class` page; the image / category / tag collections are
 * EC-CUBE-runtime fragments dropped as enumerated residuals.
 *
 * VALIDATION AUTHORITY STAYS WITH the Be domain — this form is a
 * field-definition + renderer only (see var/templates/README.md).
 */
final class AdminProductEditForm extends AbstractForm
{
    /** @var array<int, string> — numeric-string keys coerce to int (ProductEntity::STATUS_*) */
    private const STATUS_OPTIONS = [
        '1' => '公開',
        '2' => '非公開',
        '3' => '廃止',
    ];

    #[Override]
    public function init(): void
    {
        $this->setField('productName', 'text')
            ->setAttribs(['id' => 'product_name', 'class' => 'form-control', 'placeholder' => '商品名']);
        $this->setField('productCode', 'text')
            ->setAttribs(['id' => 'product_code', 'class' => 'form-control', 'placeholder' => '商品コード']);
        $this->setField('price02', 'text')
            ->setAttribs(['id' => 'product_price02', 'class' => 'form-control']);
        $this->setField('stock', 'text')
            ->setAttribs(['id' => 'product_stock', 'class' => 'form-control']);
        $this->setField('productStatus', 'select')
            ->setAttribs(['id' => 'product_status', 'class' => 'form-select'])
            ->setOptions(self::STATUS_OPTIONS);
        $this->setField('description', 'textarea')
            ->setAttribs(['id' => 'product_description_detail', 'class' => 'form-control', 'rows' => '8']);
        $this->setField('searchWord', 'text')
            ->setAttribs(['id' => 'product_search_word', 'class' => 'form-control']);
        $this->setField('note', 'textarea')
            ->setAttribs(['id' => 'product_note', 'class' => 'form-control', 'rows' => '4']);

        // Non-authoritative structural check only — authority is the Be domain.
        $this->filter->validate('productName')->isNotBlank();
    }

    /**
     * Pre-populates the editor with a product header projection.
     *
     * @param array<string, scalar|null> $values
     */
    public function fillValues(array $values): void
    {
        $this->fill($values);
    }
}
