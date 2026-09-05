<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Module;

use MyVendor\BeMart\Compatibility\Eccube\EccubeClientIp;
use PHPUnit\Framework\TestCase;

/**
 * Boundary contract of the client IP adapter: the login throttle key and
 * the audit row's client_ip both come from this one resolution.
 */
final class EccubeClientIpTest extends TestCase
{
    private array $serverBackup;

    protected function setUp(): void
    {
        $this->serverBackup = $_SERVER;
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->serverBackup;
    }

    public function testReturnsRemoteAddrWhenNoForwardedHeaderIsPresent(): void
    {
        unset($_SERVER['HTTP_X_FORWARDED_FOR']);
        $_SERVER['REMOTE_ADDR'] = '203.0.113.7';

        $this->assertSame('203.0.113.7', (new EccubeClientIp())->address());
    }

    public function testReturnsTheLastForwardedHopWhenTheHeaderIsPresent(): void
    {
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '198.51.100.9, 203.0.113.7';
        $_SERVER['REMOTE_ADDR'] = '10.0.0.5';

        $this->assertSame('203.0.113.7', (new EccubeClientIp())->address());
    }

    /** The last hop is the nearest proxy's; a client-supplied earlier hop cannot displace it. */
    public function testClientSuppliedHeaderCannotDisplaceTheProxyAppendedLastHop(): void
    {
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.99, 198.51.100.9';
        $_SERVER['REMOTE_ADDR'] = '10.0.0.5';

        $this->assertSame('198.51.100.9', (new EccubeClientIp())->address());
    }

    public function testEmptyForwardedHeaderFallsBackToRemoteAddr(): void
    {
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '';
        $_SERVER['REMOTE_ADDR'] = '203.0.113.7';

        $this->assertSame('203.0.113.7', (new EccubeClientIp())->address());
    }

    /** CLI and internal calls have no remote address; record an empty IP rather than a lie. */
    public function testMissingRemoteAddrRecordsAnEmptyString(): void
    {
        unset($_SERVER['HTTP_X_FORWARDED_FOR'], $_SERVER['REMOTE_ADDR']);

        $this->assertSame('', (new EccubeClientIp())->address());
    }
}
