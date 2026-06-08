<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\EntryPoint;

use MyVendor\BeMart\BootstrapContextResolver;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

use function getenv;
use function putenv;

final class BootstrapContextTest extends TestCase
{
    private string|false $appContextBefore;

    protected function setUp(): void
    {
        $this->appContextBefore = getenv('APP_CONTEXT');
    }

    protected function tearDown(): void
    {
        if ($this->appContextBefore === false) {
            putenv('APP_CONTEXT');

            return;
        }

        putenv('APP_CONTEXT=' . $this->appContextBefore);
    }

    public function testMissingAppContextUsesEntrypointDefault(): void
    {
        putenv('APP_CONTEXT');

        $this->assertSame('cli-html-hal-app', $this->context('cli-html-hal-app'));
    }

    public function testShortAppContextAliasKeepsCliVariant(): void
    {
        putenv('APP_CONTEXT=html-test');

        $this->assertSame('cli-html-test-hal-api-app', $this->context('cli-html-hal-app'));
    }

    public function testUnknownAppContextPassesThroughForCleanError(): void
    {
        putenv('APP_CONTEXT=nope');

        $this->assertSame('nope', $this->context('cli-hal-api-app'));
    }

    /** @param non-empty-string $defaultContext */
    private function context(string $defaultContext): string
    {
        $method = new ReflectionMethod(BootstrapContextResolver::class, 'resolve');

        /** @var string */
        return $method->invoke(new BootstrapContextResolver(), $defaultContext);
    }
}
