<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Module;

use MyVendor\BeMart\Auth\CookieSessionStarter;
use MyVendor\BeMart\Auth\SessionStarterInterface;
use MyVendor\BeMart\Module\EccubeModule;
use MyVendor\BeMart\Module\HtmlModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;

/**
 * #93 moved session-start policy out of Auth/ adapter internals and into context/DI. This is the
 * test that actually holds that boundary: without it, deleting the bind() lines in HtmlModule /
 * EccubeModule stays green (each adapter's constructor default silently covers for a missing
 * binding), and the "context decides" contract becomes decorative.
 */
final class SessionStarterBindingTest extends TestCase
{
    public function testHtmlModuleBindsSessionStarterToCookieSessionStarter(): void
    {
        $injector = new Injector(new HtmlModule());

        $this->assertInstanceOf(CookieSessionStarter::class, $injector->getInstance(SessionStarterInterface::class));
    }

    public function testEccubeModuleBindsSessionStarterToCookieSessionStarter(): void
    {
        $injector = new Injector(new EccubeModule());

        $this->assertInstanceOf(CookieSessionStarter::class, $injector->getInstance(SessionStarterInterface::class));
    }
}
