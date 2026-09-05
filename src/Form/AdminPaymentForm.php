<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Form;

use Override;
use Ray\WebFormModule\AbstractForm;

/**
 * EC-CUBE 支払方法編集フォーム — Setting/Shop Tier-2.
 *
 * PORT of EC-CUBE 4.3 `Form/Type/Admin/PaymentRegisterType` leaf fields.
 * The 6-field Be projection (paymentId / paymentMethodName / charge /
 * ruleMin / ruleMax / visible) is what this form renders; dtb_payment
 * columns outside the projection (sale_type, payment_image, …) stay out
 * of scope for this GET-renderer wave.
 */
final class AdminPaymentForm extends AbstractForm
{
    #[Override]
    public function init(): void
    {
        $this->setField('paymentMethodName', 'text')
            ->setAttribs(['id' => 'payment_method', 'class' => 'form-control']);
        $this->setField('charge', 'text')
            ->setAttribs(['id' => 'payment_charge', 'class' => 'form-control']);
        $this->setField('ruleMin', 'text')
            ->setAttribs(['id' => 'payment_rule_min', 'class' => 'form-control']);
        $this->setField('ruleMax', 'text')
            ->setAttribs(['id' => 'payment_rule_max', 'class' => 'form-control']);
        $this->setField('visible', 'checkbox')
            ->setAttribs(['id' => 'payment_visible'])
            ->setOptions(['1' => '']);

        $this->filter->validate('paymentMethodName')->isNotBlank();
    }

    /**
     * Pre-populates the editor with a payment-master row.
     *
     * @param array<string, scalar|null> $values
     */
    public function fillValues(array $values): void
    {
        $this->fill($values);
    }
}
