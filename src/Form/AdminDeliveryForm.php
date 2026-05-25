<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Form;

use Override;
use Ray\WebFormModule\AbstractForm;

/**
 * EC-CUBE 配送方法編集フォーム — Setting/Shop Tier-2.
 *
 * PORT of EC-CUBE 4.3 `Form/Type/Admin/DeliveryType` leaf fields. The
 * 3-field Be projection (deliveryId / deliveryName / visible) is what
 * this form renders; dtb_delivery columns outside the projection
 * (service_name, confirm_url, product_type, …) stay out of scope for
 * this GET-renderer wave.
 */
final class AdminDeliveryForm extends AbstractForm
{
    #[Override]
    public function init(): void
    {
        $this->setField('name', 'text')
            ->setAttribs(['id' => 'delivery_name', 'class' => 'form-control']);
        $this->setField('visible', 'checkbox')
            ->setAttribs(['id' => 'delivery_visible'])
            ->setOptions(['1' => '']);

        $this->filter->validate('name')->isNotBlank();
    }

    /**
     * Pre-populates the editor with a delivery-master row.
     *
     * @param array<string, scalar|null> $values
     */
    public function fillValues(array $values): void
    {
        $this->fill($values);
    }
}
