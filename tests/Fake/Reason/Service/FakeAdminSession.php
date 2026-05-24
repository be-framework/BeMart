<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Fake\Service;

use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Service\SessionInterface;
use Override;

/**
 * In-memory AdminSession fake.
 *
 * Holds a fixed adminId (or null for anonymous-as-admin) for the
 * lifetime of the injector. Tests that need a different admin override
 * the AppModule binding with a fresh `FakeAdminSession` instance — same
 * convention as the customer-side {@see FakeSession}.
 *
 * Default in AppModule binds `FakeAdminSession(null)` matching the
 * customer side default (anonymous browser). Tests that exercise an
 * admin-authenticated path rebind via override module — see the
 * `rebindSession` helper pattern in ChangeResourceTest / LogoutResourceTest
 * but rebinding {@see AdminSessionInterface} instead of SessionInterface.
 */
final class FakeAdminSession implements AdminSessionInterface
{
    /** @param non-empty-string|null $adminId */
    public function __construct(
        private readonly string|null $adminId = null,
    ) {
    }

    /** @return non-empty-string|null */
    #[Override]
    public function adminId(): string|null
    {
        return $this->adminId;
    }
}
