<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Auth;

use Override;

/**
 * Test-null {@see SessionStarterInterface}: never touches PHP's session machinery.
 *
 * Bound by {@see \MyVendor\BeMart\Module\FakeModule} for hypermedia/fake tests, which stand up
 * `$_SESSION` state themselves (via fixtures like `FakeAdminSession`) and must not have a real
 * `session_start()` call race or interfere with that. Distinct from {@see CookieSessionStarter}'s
 * own CLI no-op guard: that guard is about the SAPI, not the test intent, and would not help a
 * context exercised through something other than CLI. See #93.
 */
final class NullSessionStarter implements SessionStarterInterface
{
    #[Override]
    public function ensureStarted(): void
    {
    }
}
