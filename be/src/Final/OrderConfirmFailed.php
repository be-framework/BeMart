<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Reason\PaymentFailureCase;
use Ray\InputQuery\Attribute\Input;

/**
 * Final — proof that PaymentMethod::verify() rejected the pre-order; the
 * customer is bounced to the ShoppingError state in ALPS.
 *
 * Selected by the Be Framework when OrderConfirming's $being discriminator
 * is a PaymentFailureCase. EC-CUBE's controller calls $this->addError() per
 * error and redirects; here the Final exposes the list as `errors`.
 */
final readonly class OrderConfirmFailed
{
    public string $preOrderId;
    public int $paymentMethodId;

    /** @var list<string> */
    public array $errors;

    public function __construct(
        #[Input] public PaymentFailureCase $being,
        #[Input] string $preOrderId,
        #[Input] int $paymentMethodId,
    ) {
        $this->preOrderId = $preOrderId;
        $this->paymentMethodId = $paymentMethodId;
        $this->errors = $being->errors;
    }
}
