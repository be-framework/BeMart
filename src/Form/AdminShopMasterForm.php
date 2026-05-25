<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Form;

use Override;
use Ray\WebFormModule\AbstractForm;

/**
 * EC-CUBE 店舗基本情報フォーム — Setting/Shop Tier-2.
 *
 * PORT of EC-CUBE 4.3 `Form/Type/Admin/ShopMasterType` leaf fields.
 * dtb_base_info is a single-row table; the {@see \MyVendor\BeMart\Be\Final\BaseInfoFetched}
 * projection drives the field set. `pref` is a select whose master-data
 * options (mtb_pref) are not carried in the read body, so its option
 * list is left empty for this GET-renderer wave.
 */
final class AdminShopMasterForm extends AbstractForm
{
    #[Override]
    public function init(): void
    {
        $this->setField('shop_name', 'text')
            ->setAttribs(['id' => 'shop_master_shop_name', 'class' => 'form-control']);
        $this->setField('shop_kana', 'text')
            ->setAttribs(['id' => 'shop_master_shop_kana', 'class' => 'form-control']);
        $this->setField('shop_name_eng', 'text')
            ->setAttribs(['id' => 'shop_master_shop_name_eng', 'class' => 'form-control']);
        $this->setField('company_name', 'text')
            ->setAttribs(['id' => 'shop_master_company_name', 'class' => 'form-control']);
        $this->setField('postal_code', 'text')
            ->setAttribs(['id' => 'shop_master_postal_code', 'class' => 'form-control']);
        $this->setField('pref', 'select')
            ->setAttribs(['id' => 'shop_master_pref', 'class' => 'form-select'])
            ->setOptions([]);
        $this->setField('addr01', 'text')
            ->setAttribs(['id' => 'shop_master_addr01', 'class' => 'form-control']);
        $this->setField('addr02', 'text')
            ->setAttribs(['id' => 'shop_master_addr02', 'class' => 'form-control']);
        $this->setField('phone_number', 'text')
            ->setAttribs(['id' => 'shop_master_phone_number', 'class' => 'form-control']);
        $this->setField('business_hour', 'text')
            ->setAttribs(['id' => 'shop_master_business_hour', 'class' => 'form-control']);
        $this->setField('email01', 'text')
            ->setAttribs(['id' => 'shop_master_email01', 'class' => 'form-control']);
        $this->setField('shop_message', 'textarea')
            ->setAttribs(['id' => 'shop_master_shop_message', 'class' => 'form-control', 'rows' => '6']);

        $this->filter->validate('shop_name')->isNotBlank();
    }

    /**
     * Pre-populates the editor with the dtb_base_info row.
     *
     * @param array<string, scalar|null> $values
     */
    public function fillValues(array $values): void
    {
        $this->fill($values);
    }
}
