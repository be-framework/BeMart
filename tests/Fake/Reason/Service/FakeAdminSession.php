<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Fake\Service;

use MyVendor\BeMart\Be\Reason\Service\AdminSession;

/**
 * In-memory admin session snapshot.
 *
 * Tests that need a different admin override the binding with a fresh instance.
 * Customer and admin identities stay separate, matching EC-CUBE firewalls.
 */
final readonly class FakeAdminSession extends AdminSession
{
}
