<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\App\Customer;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Be\Reason\Query\CustomerQueryInterface;

/**
 * One customer's own name and email, by id
 *
 * The pages that show it used to run the query themselves. It is here so the read lives in the
 * resource graph, and so the decision not to cache it is written down: this is one person's
 * personal data, and an entry keyed by customer id would be served to whoever supplies the id.
 * No cache attribute, deliberately - the callers are authenticated screens that must ask every time.
 *
 * 404 rather than an empty body for an unknown id: a session that points at a customer who no
 * longer exists is the caller's signal to treat the request as unauthenticated.
 */
class Profile extends ResourceObject
{
    public function __construct(
        private readonly CustomerQueryInterface $customerQuery,
    ) {
    }

    public function onGet(string $customerId): static
    {
        $customer = $this->customerQuery->item($customerId);
        if ($customer === null) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['customerId' => $customerId];

            return $this;
        }

        $this->code = Code::OK;
        $this->body = [
            'customerId' => $customer->customerId,
            'email' => $customer->email,
            'name01' => $customer->name01,
            'name02' => $customer->name02,
        ];

        return $this;
    }
}
