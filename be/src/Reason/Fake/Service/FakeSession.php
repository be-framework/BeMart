<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Fake\Service;

use MyVendor\BeMart\Be\Reason\Service\SessionInterface;
use Override;

/**
 * In-memory Session fake.
 *
 * Holds a fixed customerId (or null for anonymous) for the lifetime of
 * the injector. Tests that need a different customer override the
 * AppModule binding with a fresh `FakeSession` instance — there is no
 * `loginAs()` mutator on purpose, to keep request-scope semantics
 * obvious (you don't "log in mid-request" in a stateless framework).
 *
 * Default in AppModule binds `FakeSession('customer-001')` to match the
 * `aaaa…` happy-path pre-order fixture.
 */
final class FakeSession implements SessionInterface
{
    /** @param non-empty-string|null $customerId */
    public function __construct(
        private readonly string|null $customerId = null,
    ) {
    }

    /** @return non-empty-string|null */
    #[Override]
    public function customerId(): string|null
    {
        return $this->customerId;
    }
}
