<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Module;

use MyVendor\BeMart\Auth\EccubeSharedCsrfTokenAdapter;
use MyVendor\BeMart\Module\BeMartTwigExtension;
use PHPUnit\Framework\TestCase;

use function array_map;
use function strlen;

final class BeMartTwigExtensionTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($_SESSION[EccubeSharedCsrfTokenAdapter::SESSION_KEY]);
    }

    public function testExposesCsrfTwigFunctions(): void
    {
        $extension = new BeMartTwigExtension();
        $names = array_map(static fn ($function): string => $function->getName(), $extension->getFunctions());

        self::assertContains('csrf_token', $names);
        self::assertContains('csrf_token_for_anchor', $names);
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
