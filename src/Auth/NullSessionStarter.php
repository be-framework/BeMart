<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Auth;

use Override;

/**
 * Test-null {@see SessionStarterInterface}: never touches PHP's session machinery.
 *
 * For a context that binds a real session/CSRF adapter but must not start a session itself -
 * e.g. a fixture that already manages $_SESSION directly, or a CLI/HAL context with no cookie to
 * honour. See #93.
 */
final class NullSessionStarter implements SessionStarterInterface
{
    #[Override]
    public function ensureStarted(): void
    {
    }
}
