<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Auth;

/**
 * Starts (or declines to start) the PHP session a cookie-backed session/CSRF adapter reads from.
 *
 * The cookie name and start policy are a context/DI decision, not something a Resource or Auth
 * adapter should hardcode - see issue #93. {@see CookieSessionStarter} carries the production
 * cookie policy, bound explicitly by {@see \MyVendor\BeMart\Module\HtmlModule} and
 * {@see \MyVendor\BeMart\Module\EccubeModule}, and used as every adapter's constructor default so
 * direct instantiation (unit tests) keeps working without a context. {@see NullSessionStarter}
 * is the test-null implementation, bound by {@see \MyVendor\BeMart\Module\FakeModule} for
 * hypermedia/fake test contexts.
 */
interface SessionStarterInterface
{
    public function ensureStarted(): void;
}
