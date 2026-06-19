<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Module;

use MyVendor\BeMart\Auth\EccubeSharedCsrfTokenAdapter;
use MyVendor\BeMart\Auth\EccubeSharedSessionAdapter;
use MyVendor\BeMart\Module\BeMartTwigExtension;
use PHPUnit\Framework\TestCase;

use function array_map;
use function strlen;

final class BeMartTwigExtensionTest extends TestCase
{
    protected function tearDown(): void
    {
        unset(
            $_SESSION[EccubeSharedCsrfTokenAdapter::SESSION_KEY],
            $_SESSION[EccubeSharedSessionAdapter::CUSTOMER_ID_KEY],
        );
    }

    public function testExposesCsrfTwigFunctions(): void
    {
        $extension = new BeMartTwigExtension();
        $names = array_map(static fn ($function): string => $function->getName(), $extension->getFunctions());

        self::assertContains('csrf_token', $names);
        self::assertContains('csrf_token_for_anchor', $names);
        self::assertContains('is_logged_in', $names);
    }

    public function testIsLoggedInReflectsCustomerSession(): void
    {
        $extension = new BeMartTwigExtension();

        unset($_SESSION[EccubeSharedSessionAdapter::CUSTOMER_ID_KEY]);
        self::assertFalse($extension->isLoggedIn());

        $_SESSION[EccubeSharedSessionAdapter::CUSTOMER_ID_KEY] = 'customer-001';
        self::assertTrue($extension->isLoggedIn());

        $_SESSION[EccubeSharedSessionAdapter::CUSTOMER_ID_KEY] = '';
        self::assertFalse($extension->isLoggedIn());
    }

    public function testCsrfTokenUsesSessionReferenceWhenAvailable(): void
    {
        $_SESSION[EccubeSharedCsrfTokenAdapter::SESSION_KEY] = 'session-token';
        $extension = new BeMartTwigExtension();

        self::assertSame('session-token', $extension->csrfToken('admin_category'));
        self::assertSame('session-token', $extension->csrfTokenForAnchor('admin_category'));
    }

    public function testCsrfTokenGeneratesNonEmptyFallback(): void
    {
        unset($_SESSION[EccubeSharedCsrfTokenAdapter::SESSION_KEY]);
        $extension = new BeMartTwigExtension();

        self::assertGreaterThan(0, strlen($extension->csrfToken('cart')));
    }
}
