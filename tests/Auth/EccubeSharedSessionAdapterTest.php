<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Auth;

use MyVendor\BeMart\Auth\EccubeSharedSessionAdapter;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the Slice 7 production session adapter.
 *
 * The adapter has two resolution paths:
 *   1. $_SESSION[customer_id] populated (HTTP context, or test fixture)
 *   2. Otherwise anonymous (null)
 */
final class EccubeSharedSessionAdapterTest extends TestCase
{
    protected function setUp(): void
    {
        unset($_SESSION[EccubeSharedSessionAdapter::CUSTOMER_ID_KEY]);
    }

    protected function tearDown(): void
    {
        unset($_SESSION[EccubeSharedSessionAdapter::CUSTOMER_ID_KEY]);
    }

    public function testReturnsCustomerIdFromSession(): void
    {
        $_SESSION[EccubeSharedSessionAdapter::CUSTOMER_ID_KEY] = 'customer-001';

        $adapter = new EccubeSharedSessionAdapter();

        $this->assertSame('customer-001', $adapter->customerId);
    }

    public function testReturnsNullWhenSessionKeyAbsent(): void
    {
        $adapter = new EccubeSharedSessionAdapter();

        $this->assertNull($adapter->customerId);
    }

    public function testEmptyStringSessionTreatedAsAnonymous(): void
    {
        $_SESSION[EccubeSharedSessionAdapter::CUSTOMER_ID_KEY] = '';

        $adapter = new EccubeSharedSessionAdapter();

        $this->assertNull($adapter->customerId);
    }

    public function testNonStringSessionTreatedAsAnonymous(): void
    {
        // Defensive: someone misuses the session key with a non-string
        // value (e.g. an int customerId). Adapter rejects rather than
        // silently coercing — null is treated as anonymous downstream.
        $_SESSION[EccubeSharedSessionAdapter::CUSTOMER_ID_KEY] = 123;

        $adapter = new EccubeSharedSessionAdapter();

        $this->assertNull($adapter->customerId);
    }

    public function testCustomSessionKeyHonored(): void
    {
        // Multi-tenant / non-default deployments can configure a different
        // key (e.g. mirror a different attribute). Constructor parameter
        // is the only injection point for this.
        $_SESSION['alt_customer_field'] = 'customer-alt';

        $adapter = new EccubeSharedSessionAdapter(
            sessionKey: 'alt_customer_field',
        );

        $this->assertSame('customer-alt', $adapter->customerId);
    }
}
