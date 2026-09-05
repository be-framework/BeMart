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
use MyVendor\BeMart\Be\Reason\Fake\Service\MutableClientIp;
use MyVendor\BeMart\Be\Reason\Query\LoginAttemptGateInterface;
use MyVendor\BeMart\Be\Reason\Service\ClientIpInterface;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function count;
use function dirname;
use function is_dir;
use function mkdir;

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
            $this->failureCount('test-admin', FakeClientIp::ADDRESS),
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
        $this->assertSame(0, $this->failureCount('test-admin', FakeClientIp::ADDRESS));

        // The next run of failures gets the full allowance again.
        $this->burnFailures('test-admin', LoginAttemptGateInterface::MAX_FAILURES - 1);
        $this->assertSame(LoginAttemptGateInterface::MAX_FAILURES - 1, $this->failureCount('test-admin', FakeClientIp::ADDRESS));
    }

    public function testOneLoginIdsFailuresDoNotThrottleAnother(): void
    {
        $this->burnFailures('no-such-admin', LoginAttemptGateInterface::MAX_FAILURES);

        $final = ($this->becoming)(new AdminLoginInput(loginId: 'test-admin', password: self::PASSWORD));

        $this->assertInstanceOf(AdminAuthenticated::class, $final);
    }

    /**
     * One client's failures leave the same loginId open for another
     * client — and the second client's success does not lift the first
     * client's lock.
     */
    public function testFailuresFromOneClientDoNotThrottleAnotherClientAgainstTheSameLoginId(): void
    {
        $clientIp = new MutableClientIp('192.0.2.10');
        [$becoming, $history] = $this->injectorWithClientIp($clientIp);
        $this->burnFailuresWith($becoming, 'test-admin', LoginAttemptGateInterface::MAX_FAILURES);

        $clientIp->address = '192.0.2.99';
        $final = ($becoming)(new AdminLoginInput(loginId: 'test-admin', password: self::PASSWORD));
        $this->assertInstanceOf(AdminAuthenticated::class, $final);
        $this->assertSame(
            LoginAttemptGateInterface::MAX_FAILURES,
            $history->failuresSinceLastSuccess('test-admin', '192.0.2.10', LoginAttemptGateInterface::WINDOW_MINUTES)->count,
        );
    }

    /** A success from client A clears client A's own counter. */
    public function testASuccessFromClientAClearsClientAsCounter(): void
    {
        $clientIp = new MutableClientIp('192.0.2.10');
        [$becoming, $history] = $this->injectorWithClientIp($clientIp);
        $this->burnFailuresWith($becoming, 'test-admin', LoginAttemptGateInterface::MAX_FAILURES - 1);
        $this->assertSame(
            LoginAttemptGateInterface::MAX_FAILURES - 1,
            $history->failuresSinceLastSuccess('test-admin', '192.0.2.10', LoginAttemptGateInterface::WINDOW_MINUTES)->count,
        );

        ($becoming)(new AdminLoginInput(loginId: 'test-admin', password: self::PASSWORD));
        $this->assertSame(
            0,
            $history->failuresSinceLastSuccess('test-admin', '192.0.2.10', LoginAttemptGateInterface::WINDOW_MINUTES)->count,
        );
    }

    /**
     * The loose account counter ignores the client: one failure per client
     * (so no per-client counter moves) still refuses the loginId for a
     * fresh client once MAX_ACCOUNT_FAILURES is crossed.
     */
    public function testFailuresAcrossClientsRefuseTheLoginIdAtTheAccountThreshold(): void
    {
        $clientIp = new MutableClientIp('192.0.2.100');
        [$becoming, $history] = $this->injectorWithClientIp($clientIp);

        for ($i = 0; $i < LoginAttemptGateInterface::MAX_ACCOUNT_FAILURES; $i++) {
            $clientIp->address = '192.0.2.' . (100 + $i);
            $this->burnFailuresWith($becoming, 'test-admin', 1);
        }
        $this->assertSame(
            LoginAttemptGateInterface::MAX_ACCOUNT_FAILURES,
            $history->accountFailuresSinceLastSuccess('test-admin', LoginAttemptGateInterface::WINDOW_MINUTES)->count,
        );

        $this->expectException(LoginAttemptsExceededException::class);
        $clientIp->address = '192.0.2.250';
        ($becoming)(new AdminLoginInput(loginId: 'test-admin', password: self::PASSWORD));
    }

    /**
     * Build a becoming + audit store whose ClientIpInterface is `$clientIp`,
     * so a test can change the throttle key while the attempts share one
     * in-memory store.
     *
     * @return array{BecomingInterface, InMemoryLoginHistoryStorage}
     */
    private function injectorWithClientIp(ClientIpInterface $clientIp): array
    {
        $module = new TestModule(new Meta('MyVendor\\BeMart', 'test'));
        $module->override(new class ($clientIp) extends AbstractModule {
            public function __construct(private readonly ClientIpInterface $clientIp)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(ClientIpInterface::class)->toInstance($this->clientIp);
            }
        });

        // Own tmp dir: this injector carries a per-test override, so its
        // generated proxies must not land among the ones the shared `test`
        // dir already holds for the default module.
        $tmpDir = dirname(__DIR__, 2) . '/var/tmp/test-login-audit';
        if (! is_dir($tmpDir)) {
            mkdir($tmpDir, 0777, true);
        }

        $injector = new Injector($module, $tmpDir);

        return [
            $injector->getInstance(BecomingInterface::class),
            $injector->getInstance(InMemoryLoginHistoryStorage::class),
        ];
    }

    private function burnFailures(string $loginId, int $times): void
    {
        $this->burnFailuresWith($this->becoming, $loginId, $times);
    }

    private function burnFailuresWith(BecomingInterface $becoming, string $loginId, int $times): void
    {
        for ($i = 0; $i < $times; $i++) {
            try {
                ($becoming)(new AdminLoginInput(loginId: $loginId, password: 'not-the-right-password'));
                $this->fail('expected AdminLoginFailedException');
            } catch (AdminLoginFailedException) {
            }
        }
    }

    private function failureCount(string $loginId, string $clientIp): int
    {
        return $this->history
            ->failuresSinceLastSuccess($loginId, $clientIp, LoginAttemptGateInterface::WINDOW_MINUTES)
            ->count;
    }

    private function accountFailureCount(string $loginId): int
    {
        return $this->history
            ->accountFailuresSinceLastSuccess($loginId, LoginAttemptGateInterface::WINDOW_MINUTES)
            ->count;
    }
}
