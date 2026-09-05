<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Domain;

use BEAR\AppMeta\Meta;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\LoginAttemptsExceededException;
use MyVendor\BeMart\Be\Exception\TwoFactorAuthFailedException;
use MyVendor\BeMart\Be\Final\TwoFactorAuthVerified;
use MyVendor\BeMart\Be\Input\VerifyTwoFactorAuthInput;
use MyVendor\BeMart\Be\Reason\Fake\Query\InMemoryLoginHistoryStorage;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeTwoFactorAuth;
use MyVendor\BeMart\Be\Reason\Query\LoginAttemptGateInterface;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;

use function dirname;

final class TwoFactorAuthVerifiedTest extends TestCase
{
    private BecomingInterface $becoming;
    private InMemoryLoginHistoryStorage $history;

    protected function setUp(): void
    {
        $injector = new Injector(new TestModule(new Meta('MyVendor\\BeMart', 'test')), dirname(__DIR__, 2) . '/var/tmp/test');
        $this->becoming = $injector->getInstance(BecomingInterface::class);
        $this->history = $injector->getInstance(InMemoryLoginHistoryStorage::class);
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

    /**
     * The code is six digits and the verifier accepts a ±1 step window,
     * so the retries have to be bounded or the challenge is guessable.
     */
    public function testCodeCannotBeRetriedIndefinitely(): void
    {
        for ($i = 0; $i < LoginAttemptGateInterface::MAX_FAILURES; $i++) {
            try {
                ($this->becoming)(new VerifyTwoFactorAuthInput(loginId: 'test-admin', deviceToken: '000000'));
                $this->fail('expected TwoFactorAuthFailedException');
            } catch (TwoFactorAuthFailedException) {
            }
        }

        $this->expectException(LoginAttemptsExceededException::class);
        ($this->becoming)(new VerifyTwoFactorAuthInput(
            loginId: 'test-admin',
            deviceToken: FakeTwoFactorAuth::VALID_TOKEN,
        ));
    }

    public function testEachVerificationIsAudited(): void
    {
        ($this->becoming)(new VerifyTwoFactorAuthInput(
            loginId: 'test-admin',
            deviceToken: FakeTwoFactorAuth::VALID_TOKEN,
        ));
        $this->assertTrue($this->history->list()[0]->success);

        try {
            ($this->becoming)(new VerifyTwoFactorAuthInput(loginId: 'test-admin', deviceToken: '000000'));
        } catch (TwoFactorAuthFailedException) {
        }

        $newest = $this->history->list()[0];
        $this->assertSame('test-admin', $newest->loginId);
        $this->assertFalse($newest->success);
    }
}
