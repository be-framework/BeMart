<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Domain;

use BEAR\AppMeta\Meta;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Be\Exception\AdminLoginFailedException;
use MyVendor\BeMart\Be\Exception\LoginAttemptsExceededException;
use MyVendor\BeMart\Be\Final\AdminAuthenticated;
use MyVendor\BeMart\Be\Input\AdminLoginInput;
use MyVendor\BeMart\Be\Reason\Fake\Query\InMemoryLoginHistoryStorage;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeClientIp;
use MyVendor\BeMart\Be\Reason\Query\LoginAttemptGateInterface;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;

use function count;
use function dirname;

/**
 * Audit + throttle contract of the admin password stage.
 *
 * Complements {@see AdminAuthenticatedTest}, which owns the
 * authentication verdicts themselves.
 */
final class AdminLoginAuditTest extends TestCase
{
    private const PASSWORD = 'local-dev-admin-password';

    private BecomingInterface $becoming;
    private InMemoryLoginHistoryStorage $history;

    protected function setUp(): void
    {
        $injector = new Injector(
            new TestModule(new Meta('MyVendor\\BeMart', 'test')),
            dirname(__DIR__, 2) . '/var/tmp/test',
        );
        $this->becoming = $injector->getInstance(BecomingInterface::class);
        $this->history = $injector->getInstance(InMemoryLoginHistoryStorage::class);
    }

    public function testSuccessfulLoginAppendsSuccessRow(): void
    {
        $final = ($this->becoming)(new AdminLoginInput(loginId: 'test-admin', password: self::PASSWORD));

        $this->assertInstanceOf(AdminAuthenticated::class, $final);
        $newest = $this->history->list()[0];
        $this->assertSame('test-admin', $newest->loginId);
        $this->assertTrue($newest->success);
        $this->assertSame(FakeClientIp::ADDRESS, $newest->clientIp);
    }

    public function testUnknownLoginIdAppendsFailureRowForTheSubmittedId(): void
    {
        $this->assertFailureRecorded('no-such-admin', self::PASSWORD);
    }

    public function testWrongPasswordAppendsFailureRow(): void
    {
        $this->assertFailureRecorded('test-admin', 'not-the-right-password');
    }

    public function testDeprovisionedAdminAppendsFailureRow(): void
    {
        $this->assertFailureRecorded('deleted-admin', self::PASSWORD);
    }

    /** Every rejection is auditable, and the row carries the loginId as submitted. */
    private function assertFailureRecorded(string $loginId, string $password): void
    {
        try {
            ($this->becoming)(new AdminLoginInput(loginId: $loginId, password: $password));
            $this->fail('expected AdminLoginFailedException');
        } catch (AdminLoginFailedException) {
        }

        $newest = $this->history->list()[0];
        $this->assertSame($loginId, $newest->loginId);
        $this->assertFalse($newest->success);
        $this->assertSame(FakeClientIp::ADDRESS, $newest->clientIp);
    }

    public function testAttemptAfterThresholdIsRefusedEvenWithTheCorrectPassword(): void
    {
        $this->burnFailures('test-admin', LoginAttemptGateInterface::MAX_FAILURES);

        $this->expectException(LoginAttemptsExceededException::class);
        ($this->becoming)(new AdminLoginInput(loginId: 'test-admin', password: self::PASSWORD));
    }

    public function testRefusedAttemptIsNotItselfCountedOrLogged(): void
    {
        $this->burnFailures('test-admin', LoginAttemptGateInterface::MAX_FAILURES);
        $rowsBefore = $this->history->list();

        try {
            ($this->becoming)(new AdminLoginInput(loginId: 'test-admin', password: self::PASSWORD));
        } catch (LoginAttemptsExceededException) {
        }

        $this->assertCount(count($rowsBefore), $this->history->list());
        $this->assertSame(
            LoginAttemptGateInterface::MAX_FAILURES,
            $this->failureCount('test-admin'),
        );
    }

    public function testThrottleCountsBySubmittedLoginIdSoUnregisteredIdsAreRefusedToo(): void
    {
        $this->burnFailures('no-such-admin', LoginAttemptGateInterface::MAX_FAILURES);

        $this->expectException(LoginAttemptsExceededException::class);
        ($this->becoming)(new AdminLoginInput(loginId: 'no-such-admin', password: self::PASSWORD));
    }

    public function testSuccessResetsTheCounter(): void
    {
        $this->burnFailures('test-admin', LoginAttemptGateInterface::MAX_FAILURES - 1);

        ($this->becoming)(new AdminLoginInput(loginId: 'test-admin', password: self::PASSWORD));
        $this->assertSame(0, $this->failureCount('test-admin'));

        // The next run of failures gets the full allowance again.
        $this->burnFailures('test-admin', LoginAttemptGateInterface::MAX_FAILURES - 1);
        $this->assertSame(LoginAttemptGateInterface::MAX_FAILURES - 1, $this->failureCount('test-admin'));
    }

    public function testOneLoginIdsFailuresDoNotThrottleAnother(): void
    {
        $this->burnFailures('no-such-admin', LoginAttemptGateInterface::MAX_FAILURES);

        $final = ($this->becoming)(new AdminLoginInput(loginId: 'test-admin', password: self::PASSWORD));

        $this->assertInstanceOf(AdminAuthenticated::class, $final);
    }

    private function burnFailures(string $loginId, int $times): void
    {
        for ($i = 0; $i < $times; $i++) {
            try {
                ($this->becoming)(new AdminLoginInput(loginId: $loginId, password: 'not-the-right-password'));
                $this->fail('expected AdminLoginFailedException');
            } catch (AdminLoginFailedException) {
            }
        }
    }

    private function failureCount(string $loginId): int
    {
        return $this->history
            ->failuresSinceLastSuccess($loginId, LoginAttemptGateInterface::WINDOW_MINUTES)
            ->count;
    }
}
