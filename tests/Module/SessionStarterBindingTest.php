<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Module;

use BEAR\AppMeta\Meta;
use MyVendor\BeMart\Auth\CookieSessionStarter;
use MyVendor\BeMart\Auth\EccubeSharedSessionAdapter;
use MyVendor\BeMart\Auth\NullSessionStarter;
use MyVendor\BeMart\Auth\SessionStarterInterface;
use MyVendor\BeMart\Module\EccubeModule;
use MyVendor\BeMart\Module\HtmlModule;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;
use ReflectionProperty;

use function dirname;

/**
 * #93 moved session-start policy out of Auth/ adapter internals and into context/DI. This is the
 * test that actually holds that boundary: without it, deleting the bind() lines in HtmlModule /
 * EccubeModule stays green (each adapter's constructor default silently covers for a missing
 * binding), and the "context decides" contract becomes decorative.
 *
 * Cookie-name assertions read the private property via reflection: `assertInstanceOf` alone would
 * stay green for `new CookieSessionStarter('WRONG')`, since only the type - not the policy the
 * issue is actually about - would be checked.
 */
final class SessionStarterBindingTest extends TestCase
{
    private static function cookieNameOf(SessionStarterInterface $starter): string
    {
        $property = new ReflectionProperty(CookieSessionStarter::class, 'cookieName');

        return $property->getValue($starter);
    }

    public function testHtmlModuleBindsSessionStarterToCookieSessionStarter(): void
    {
        $injector = new Injector(new HtmlModule());
        $starter = $injector->getInstance(SessionStarterInterface::class);

        $this->assertInstanceOf(CookieSessionStarter::class, $starter);
        $this->assertSame(EccubeSharedSessionAdapter::COOKIE_NAME, self::cookieNameOf($starter));
    }

    public function testEccubeModuleBindsSessionStarterToCookieSessionStarter(): void
    {
        $injector = new Injector(new EccubeModule());
        $starter = $injector->getInstance(SessionStarterInterface::class);

        $this->assertInstanceOf(CookieSessionStarter::class, $starter);
        $this->assertSame(EccubeSharedSessionAdapter::COOKIE_NAME, self::cookieNameOf($starter));
    }

    public function testTestModuleBindsSessionStarterToNullSessionStarter(): void
    {
        $injector = new Injector(
            new TestModule(new Meta('MyVendor\\BeMart', 'test')),
            dirname(__DIR__, 2) . '/var/tmp/test',
        );

        $this->assertInstanceOf(NullSessionStarter::class, $injector->getInstance(SessionStarterInterface::class));
    }
}
