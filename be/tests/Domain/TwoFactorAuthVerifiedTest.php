<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Domain;

use BEAR\AppMeta\Meta;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\TwoFactorAuthFailedException;
use MyVendor\BeMart\Be\Final\TwoFactorAuthVerified;
use MyVendor\BeMart\Be\Input\VerifyTwoFactorAuthInput;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeTwoFactorAuth;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;

use function dirname;

final class TwoFactorAuthVerifiedTest extends TestCase
{
    private BecomingInterface $becoming;

    protected function setUp(): void
    {
        $injector = new Injector(new TestModule(new Meta('MyVendor\\BeMart', 'test')), dirname(__DIR__, 2) . '/var/tmp/test');
        $this->becoming = $injector->getInstance(BecomingInterface::class);
    }

    public function testHappyPathVerifiesToken(): void
    {
        $final = ($this->becoming)(new VerifyTwoFactorAuthInput(
            loginId: 'test-admin',
            deviceToken: FakeTwoFactorAuth::VALID_TOKEN,
        ));

        $this->assertInstanceOf(TwoFactorAuthVerified::class, $final);
        $this->assertSame('test-admin', $final->loginId);
    }

    public function testWrongTokenRejected(): void
    {
        $this->expectException(TwoFactorAuthFailedException::class);
        ($this->becoming)(new VerifyTwoFactorAuthInput(
            loginId: 'test-admin',
            deviceToken: '000000',
        ));
    }

    public function testUnknownAdminRejected(): void
    {
        $this->expectException(TwoFactorAuthFailedException::class);
        ($this->becoming)(new VerifyTwoFactorAuthInput(
            loginId: 'no-such-admin',
            deviceToken: FakeTwoFactorAuth::VALID_TOKEN,
        ));
    }

    public function testMalformedTokenRejectedAtSemanticBoundary(): void
    {
        $this->expectException(SemanticVariableException::class);
        ($this->becoming)(new VerifyTwoFactorAuthInput(
            loginId: 'test-admin',
            deviceToken: 'abc',
        ));
    }
}
