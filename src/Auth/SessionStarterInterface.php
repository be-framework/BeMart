<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Auth;

/**
 * Starts (or declines to start) the PHP session a cookie-backed session/CSRF adapter reads from.
 *
 * The cookie name and start policy are a context/DI decision, not something a Resource or Auth
 * adapter should hardcode - see issue #93. {@see CookieSessionStarter} carries the production
 * cookie policy; {@see NullSessionStarter} is for contexts that must never touch PHP's session
 * machinery (e.g. no HTTP origin, or a fake/test context that stands session state up itself).
 */
interface SessionStarterInterface
{
    public function ensureStarted(): void;
}
