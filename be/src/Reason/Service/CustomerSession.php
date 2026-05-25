<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

/**
 * Customer session snapshot — the AAA boundary for "who is making this request".
 *
 * The authenticated customerId is a value, not an operation. Domain code reads
 * it directly for ownership checks; authentication, cookie handling and session
 * storage are adapter concerns outside this object.
 */
abstract readonly class CustomerSession
{
    /**
     * @var non-empty-string|null customerId, or null if unauthenticated
     *
     * @psalm-taint-source session
     */
    public string|null $customerId;

    /** @param non-empty-string|null $customerId */
    public function __construct(string|null $customerId = null)
    {
        $this->customerId = $customerId;
    }
}
