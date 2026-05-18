<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Auth;

use MyVendor\BeMart\Auth\SymfonySessionAdapter;
use PHPUnit\Framework\TestCase;

use function getenv;
use function putenv;

/**
 * Unit tests for the Slice 7 production session adapter.
 *
 * The adapter has three resolution paths:
 *   1. $_SESSION[customer_id] populated (HTTP context, or test fixture)
 *   2. CLI + BEMART_CLI_CUSTOMER_ID env var (operator scripts)
 *   3. Otherwise anonymous (null)
 *
 * These tests run under PHP_SAPI=cli (PHPUnit), so the CLI fallback is
 * exercised directly. The HTTP path is covered transitively by
 * ProdModuleTest and AppEntryPointTest (subprocess).
 */
final class SymfonySessionAdapterTest extends TestCase
{
    private string|false $envBefore;

    protected function setUp(): void
    {
        $this->envBefore = getenv(SymfonySessionAdapter::CLI_ENV_VAR);
        putenv(SymfonySessionAdapter::CLI_ENV_VAR);
        unset($_SESSION[SymfonySessionAdapter::CUSTOMER_ID_KEY]);
    }

    protected function tearDown(): void
    {
        unset($_SESSION[SymfonySessionAdapter::CUSTOMER_ID_KEY]);
        if ($this->envBefore === false) {
            putenv(SymfonySessionAdapter::CLI_ENV_VAR);

            return;
        }

        putenv(SymfonySessionAdapter::CLI_ENV_VAR . '=' . $this->envBefore);
    }

    public function testReturnsCustomerIdFromSession(): void
    {
        $_SESSION[SymfonySessionAdapter::CUSTOMER_ID_KEY] = 'customer-001';

        $adapter = new SymfonySessionAdapter();

        $this->assertSame('customer-001', $adapter->customerId());
    }

    public function testReturnsNullWhenSessionAndEnvUnset(): void
    {
        $adapter = new SymfonySessionAdapter();

        $this->assertNull($adapter->customerId());
    }

    public function testCliEnvFallbackUsedWhenSessionEmpty(): void
    {
        putenv(SymfonySessionAdapter::CLI_ENV_VAR . '=customer-042');

        $adapter = new SymfonySessionAdapter();

        $this->assertSame('customer-042', $adapter->customerId());
    }

    public function testSessionTakesPriorityOverCliEnv(): void
    {
        // If both are set (operator runs CLI but the test harness also
        // pre-populates $_SESSION), the session value wins. This keeps
        // ProdModuleTest deterministic regardless of the developer's
        // local env.
        $_SESSION[SymfonySessionAdapter::CUSTOMER_ID_KEY] = 'from-session';
        putenv(SymfonySessionAdapter::CLI_ENV_VAR . '=from-env');

        $adapter = new SymfonySessionAdapter();

        $this->assertSame('from-session', $adapter->customerId());
    }

    public function testEmptyStringSessionTreatedAsAnonymous(): void
    {
        $_SESSION[SymfonySessionAdapter::CUSTOMER_ID_KEY] = '';

        $adapter = new SymfonySessionAdapter();

        $this->assertNull($adapter->customerId());
    }

    public function testNonStringSessionTreatedAsAnonymous(): void
    {
        // Defensive: someone misuses the session key with a non-string
        // value (e.g. an int customerId). Adapter rejects rather than
        // silently coercing — null is treated as anonymous downstream.
        $_SESSION[SymfonySessionAdapter::CUSTOMER_ID_KEY] = 123;

        $adapter = new SymfonySessionAdapter();

        $this->assertNull($adapter->customerId());
    }

    public function testCustomSessionKeyHonored(): void
    {
        // Multi-tenant / non-default deployments can configure a different
        // key (e.g. mirror a different attribute). Constructor parameter
        // is the only injection point for this.
        $_SESSION['alt_customer_field'] = 'customer-alt';

        $adapter = new SymfonySessionAdapter(
            sessionKey: 'alt_customer_field',
        );

        $this->assertSame('customer-alt', $adapter->customerId());
    }
}
