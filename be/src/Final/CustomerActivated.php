<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\SecretKeyNotFoundException;
use MyVendor\BeMart\Be\Reason\Query\CustomerCommandInterface;
use MyVendor\BeMart\Be\Reason\Query\CustomerQueryInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Customer activated — Final, proof a provisional customer turned
 * into an active customer.
 *
 *   ActivateCustomerInput → CustomerActivated  (this stage)
 *
 * Idempotency: the descriptor is type `idempotent` so the activate
 * step itself is a no-op when the customer is already active. The
 * MISSING-key case (no provisional customer carries the supplied
 * secretKey) raises SecretKeyNotFoundException — this is the
 * intentionally-merged "wrong key OR expired OR already-used" path.
 *
 * The Final's public surface is intentionally minimal: customerId and
 * the activated status. The plaintext email / password is not echoed.
 */
final readonly class CustomerActivated
{
    public string $customerId;
    public string $email;
    public int $customerStatus;

    public function __construct(
        #[Input] string $secretKey,
        #[Inject] CustomerQueryInterface $customerQuery,
        #[Inject] CustomerCommandInterface $customerCommand,
    ) {
        $customer = $customerQuery->bySecretKey($secretKey);
        if ($customer === null) {
            throw new SecretKeyNotFoundException();
        }

        $customerCommand->activate($customer->customerId);

        $this->customerId = $customer->customerId;
        $this->email = $customer->email;
        $this->customerStatus = 2;
    }
}
