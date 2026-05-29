<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Domain;

use BEAR\AppMeta\Meta;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Be\Exception\TwoFactorAuthFailedException;
use MyVendor\BeMart\Be\Final\TwoFactorAuthConfigured;
use MyVendor\BeMart\Be\Input\SetTwoFactorAuthInput;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeTwoFactorAuth;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;

use function dirname;

final class TwoFactorAuthConfiguredTest extends TestCase
{
    private BecomingInterface $becoming;
    private FakeTwoFactorAuth $twoFactorAuth;

    protected function setUp(): void
    {
        $injector = new Injector(new TestModule(new Meta('MyVendor\\BeMart', 'test')), dirname(__DIR__, 2) . '/var/tmp/test');
        $this->becoming = $injector->getInstance(BecomingInterface::class);
        $this->twoFactorAuth = $injector->getInstance(FakeTwoFactorAuth::class);
    }

    public function testHappyPathConfiguresDevice(): void
    {
        $final = ($this->becoming)(new SetTwoFactorAuthInput(
            loginId: 'fresh-admin',
            authKey: FakeTwoFactorAuth::FIXED_SECRET,
            deviceToken: FakeTwoFactorAuth::VALID_TOKEN,
        ));

        $this->assertInstanceOf(TwoFactorAuthConfigured::class, $final);
        $this->assertSame('fresh-admin', $final->loginId);
        $this->assertSame(FakeTwoFactorAuth::FIXED_SECRET, $final->authKey);
        $this->assertTrue($this->twoFactorAuth->isEnabled('fresh-admin'));
    }

    public function testWrongFirstCodeRejected(): void
    {
        $this->expectException(TwoFactorAuthFailedException::class);
        ($this->becoming)(new SetTwoFactorAuthInput(
            loginId: 'fresh-admin-2',
            authKey: FakeTwoFactorAuth::FIXED_SECRET,
            deviceToken: '000000',
        ));
    }
}
