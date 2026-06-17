<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Module;

use MyVendor\BeMart\Be\Reason\Service\TwoFactorAuthInterface;
use MyVendor\BeMart\Dev\DevLogin;
use MyVendor\BeMart\Dev\MagicTwoFactorAuth;
use MyVendor\BeMart\Injector;
use MyVendor\BeMart\Module\DevloginModule;
use PHPUnit\Framework\TestCase;

/**
 * Dev 2FA bypass — guard truth table, the magic verifier, and the DI override.
 *
 * The bypass exists so automation can clear the admin 2FA gate on real (SQL)
 * data. The critical property is that it can NEVER be active in production.
 */
final class DevLoginTest extends TestCase
{
    public function testActiveOnlyWhenEnvAndCliServerAndNonProd(): void
    {
        $this->assertTrue(DevLogin::active('1', 'cli-server', 'html-eccube-sql-hal-app'));
    }

    public function testInactiveWithoutEnvOptIn(): void
    {
        $this->assertFalse(DevLogin::active(false, 'cli-server', 'html-eccube-sql-hal-app'));
        $this->assertFalse(DevLogin::active('0', 'cli-server', 'html-eccube-sql-hal-app'));
    }

    public function testInactiveOutsideCliServer(): void
    {
        $this->assertFalse(DevLogin::active('1', 'fpm-fcgi', 'html-eccube-sql-hal-app'));
        $this->assertFalse(DevLogin::active('1', 'cli', 'html-eccube-sql-hal-app'));
    }

    public function testInactiveInProdContextEvenWithEnvAndCliServer(): void
    {
        $this->assertFalse(DevLogin::active('1', 'cli-server', 'prod-html-eccube-sql-app'));
    }

    public function testMagicVerifierAcceptsOnlyTheMagicCode(): void
    {
        $auth = new MagicTwoFactorAuth();

        $this->assertTrue($auth->verify('test-admin', MagicTwoFactorAuth::MAGIC));
        $this->assertTrue($auth->verifySecret('any-secret', MagicTwoFactorAuth::MAGIC));
        $this->assertTrue($auth->isEnabled('anyone'));
        $this->assertFalse($auth->verify('test-admin', '000000'));
        $this->assertFalse($auth->verifySecret('any-secret', '999999'));
    }

    public function testDevloginModuleOverridesTheTwoFactorBinding(): void
    {
        $injector = Injector::getOverrideInstance('html-test-hal-app', new DevloginModule());

        $this->assertInstanceOf(
            MagicTwoFactorAuth::class,
            $injector->getInstance(TwoFactorAuthInterface::class),
        );
    }
}
