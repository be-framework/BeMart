<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Form;

use Override;
use Ray\WebFormModule\AbstractForm;

/**
 * EC-CUBE マスタデータ管理フォーム — Setting/System Tier-2.
 *
 * PORT of EC-CUBE 4.3 `MasterdataType` / `MasterdataEditType` leaf
 * fields. The editable row collection is rendered by the template from
 * resource body rows because Ray.WebFormModule has no Symfony-style
 * collection prototype helper.
 */
final class AdminMasterDataForm extends AbstractForm
{
    #[Override]
    public function init(): void
    {
        $this->setField('masterdata', 'select')
            ->setAttribs([
                'id' => 'admin_system_masterdata_masterdata',
                'class' => 'form-select',
            ])
            ->setOptions([
                'payment' => 'dtb_payment',
                'delivery' => 'dtb_delivery',
                'tag' => 'dtb_tag',
                'className' => 'dtb_class_name',
                'classCategory' => 'dtb_class_category',
                'news' => 'dtb_news',
            ]);

        $this->setField('masterdata_name', 'hidden')
            ->setAttribs([
                'id' => 'admin_system_masterdata_edit_masterdata_name',
            ]);

        $this->filter->validate('masterdata')->isNotBlank();
    }

    /**
     * @param list<array{value: string, label: string, table: string}> $masterTypes
     */
    public function fillValues(array $masterTypes, string $selectedMaster): void
    {
        $this->fill([
            'masterdata' => $selectedMaster,
            'masterdata_name' => $selectedMaster,
        ]);
    }
}
