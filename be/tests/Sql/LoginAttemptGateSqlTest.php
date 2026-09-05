<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Sql;

use Aura\Sql\DecoratedPdo;
use BEAR\AppMeta\Meta;
use MyVendor\BeMart\Be\Exception\LoginAttemptsExceededException;
use MyVendor\BeMart\Be\Reason\Query\LoginAttemptGateInterface;
use MyVendor\BeMart\Be\Reason\Query\LoginHistoryStorageInterface;
use MyVendor\BeMart\Module\MediaQueryRuntimeModule;
use MyVendor\BeMart\Module\TestModule;
use PDO;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;
use RuntimeException;

use function assert;
use function dirname;
use function is_dir;
use function mkdir;

/**
 * MySQL contract of the login audit write and the throttle count.
 *
 * The Fake store cannot answer for these two: `tlogin_history_insert`
 * has to hit the real `login_history_status_id` FK (0 = 失敗, 1 = 成功
 * per mtb_login_history_status), and `tlogin_history_recent_failures`
 * does its windowing in SQL — `NOW() - INTERVAL :windowMinutes MINUTE`
 * plus an `id >` correlated subquery, none of which a JSONL corpus
 * exercises.
 *
 * Stale-row cases are seeded with explicit `create_date` values, because
 * append() deliberately stamps rows with the database's NOW().
 */
final class LoginAttemptGateSqlTest extends TestCase
{
    private const LOGIN_ID = 'test-admin';
    private const CLIENT_IP = '192.0.2.10';
    private const WINDOW = LoginAttemptGateInterface::WINDOW_MINUTES;

    private PDO $pdo;
    private LoginHistoryStorageInterface $history;
    private LoginAttemptGateInterface $gate;

    protected function setUp(): void
    {
        if (! isset($GLOBALS['BEMART_SQL_BOOTSTRAP'])) {
            require __DIR__ . '/bootstrap.php';
        }

        /** @var array{skip: bool, reason?: string, pdo?: PDO}|null $bootstrap */
        $bootstrap = $GLOBALS['BEMART_SQL_BOOTSTRAP'] ?? null;
        if ($bootstrap === null) {
            throw new RuntimeException('SQL bootstrap did not publish $GLOBALS[\'BEMART_SQL_BOOTSTRAP\']');
        }

        if ($bootstrap['skip']) {
            $this->markTestSkipped($bootstrap['reason'] ?? 'SQL suite disabled');
        }

        $this->pdo = $bootstrap['pdo'];
        $this->pdo->beginTransaction();

        $module = new TestModule(new Meta('MyVendor\\BeMart', 'test'));
        $module->override(new class ($this->pdo) extends AbstractModule {
            public function __construct(private readonly PDO $pdo)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->install(new MediaQueryRuntimeModule(new DecoratedPdo($this->pdo)));
            }
        });
        // Own tmp dir: this injector overrides the PDO binding, so its
        // generated proxies must not mix with the shared `test` dir's.
        $tmpDir = dirname(__DIR__, 2) . '/var/tmp/test-loginattemptgatesqltest';
        if (! is_dir($tmpDir)) {
            mkdir($tmpDir, 0777, true);
        }

        $injector = new Injector($module, $tmpDir);

        $history = $injector->getInstance(LoginHistoryStorageInterface::class);
        assert($history instanceof LoginHistoryStorageInterface);
        $gate = $injector->getInstance(LoginAttemptGateInterface::class);
        assert($gate instanceof LoginAttemptGateInterface);
        $this->history = $history;
        $this->gate = $gate;
    }

    protected function tearDown(): void
    {
        if (isset($this->pdo) && $this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    public function testAppendWritesEcCubeStatusIds(): void
    {
        $this->history->append(self::LOGIN_ID, true, self::CLIENT_IP);
        $this->history->append(self::LOGIN_ID, false, self::CLIENT_IP);

        $rows = $this->pdo
            ->query('SELECT login_history_status_id, user_name, client_ip, discriminator_type FROM dtb_login_history ORDER BY id')
            ->fetchAll(PDO::FETCH_ASSOC);

        $this->assertSame(
            [
                ['login_history_status_id' => 1, 'user_name' => self::LOGIN_ID, 'client_ip' => self::CLIENT_IP, 'discriminator_type' => 'loginhistory'],
                ['login_history_status_id' => 0, 'user_name' => self::LOGIN_ID, 'client_ip' => self::CLIENT_IP, 'discriminator_type' => 'loginhistory'],
            ],
            $rows,
        );
    }

    public function testAppendedAttemptsAreReadableNewestFirst(): void
    {
        $this->history->append('shop-owner', true, self::CLIENT_IP);
        $this->history->append(self::LOGIN_ID, false, self::CLIENT_IP);

        $entries = $this->history->list();

        $this->assertCount(2, $entries);
        $this->assertSame(self::LOGIN_ID, $entries[0]->loginId);
        $this->assertFalse($entries[0]->success);
        $this->assertTrue($entries[1]->success);
    }

    public function testFailuresAreCountedUntilASuccessClearsThem(): void
    {
        $this->assertSame(0, $this->failureCount(self::LOGIN_ID, self::CLIENT_IP));

        $this->history->append(self::LOGIN_ID, false, self::CLIENT_IP);
        $this->history->append(self::LOGIN_ID, false, self::CLIENT_IP);
        $this->assertSame(2, $this->failureCount(self::LOGIN_ID, self::CLIENT_IP));

        $this->history->append(self::LOGIN_ID, true, self::CLIENT_IP);
        $this->assertSame(0, $this->failureCount(self::LOGIN_ID, self::CLIENT_IP));

        // Same second as the success: ordering is by id, not by the
        // second-granularity create_date.
        $this->history->append(self::LOGIN_ID, false, self::CLIENT_IP);
        $this->assertSame(1, $this->failureCount(self::LOGIN_ID, self::CLIENT_IP));
    }

    public function testFailuresOlderThanTheWindowAreNotCounted(): void
    {
        $this->seedAttempt(self::LOGIN_ID, false, self::WINDOW + 1);
        $this->assertSame(0, $this->failureCount(self::LOGIN_ID, self::CLIENT_IP));

        $this->seedAttempt(self::LOGIN_ID, false, self::WINDOW - 1);
        $this->assertSame(1, $this->failureCount(self::LOGIN_ID, self::CLIENT_IP));
    }

    /** A success that has already aged out must not clear in-window failures. */
    public function testSuccessOlderThanTheWindowDoesNotClearFailures(): void
    {
        $this->seedAttempt(self::LOGIN_ID, true, self::WINDOW + 1);
        $this->seedAttempt(self::LOGIN_ID, false, self::WINDOW - 1);

        $this->assertSame(1, $this->failureCount(self::LOGIN_ID, self::CLIENT_IP));
    }

    public function testFailuresAreCountedPerLoginId(): void
    {
        $this->history->append('other-admin', false, self::CLIENT_IP);
        $this->history->append('other-admin', false, self::CLIENT_IP);

        $this->assertSame(0, $this->failureCount(self::LOGIN_ID, self::CLIENT_IP));
        $this->assertSame(2, $this->failureCount('other-admin', self::CLIENT_IP));
    }

    public function testFailuresAreCountedPerClientIp(): void
    {
        $otherClient = '192.0.2.99';
        $this->history->append(self::LOGIN_ID, false, self::CLIENT_IP);
        $this->history->append(self::LOGIN_ID, false, self::CLIENT_IP);
        $this->history->append(self::LOGIN_ID, false, $otherClient);

        $this->assertSame(2, $this->failureCount(self::LOGIN_ID, self::CLIENT_IP));
        $this->assertSame(1, $this->failureCount(self::LOGIN_ID, $otherClient));
    }

    /** Five failures from client A put client A over MAX_FAILURES. */
    public function testFiveFailuresFromClientAExceedThePerClientThreshold(): void
    {
        for ($i = 0; $i < LoginAttemptGateInterface::MAX_FAILURES; $i++) {
            $this->history->append(self::LOGIN_ID, false, self::CLIENT_IP);
        }

        $count = $this->gate->failuresSinceLastSuccess(self::LOGIN_ID, self::CLIENT_IP, self::WINDOW);
        $this->assertSame(LoginAttemptGateInterface::MAX_FAILURES, $count->count);
        $this->expectException(LoginAttemptsExceededException::class);
        $count->assertBelow(LoginAttemptGateInterface::MAX_FAILURES);
    }

    /** Client A's five failures leave client B a clean counter on the same loginId. */
    public function testFailuresFromClientADoNotThrottleClientBAgainstTheSameLoginId(): void
    {
        $clientB = '192.0.2.99';
        for ($i = 0; $i < LoginAttemptGateInterface::MAX_FAILURES; $i++) {
            $this->history->append(self::LOGIN_ID, false, self::CLIENT_IP);
        }

        $countB = $this->gate->failuresSinceLastSuccess(self::LOGIN_ID, $clientB, self::WINDOW);
        $this->assertSame(0, $countB->count);
        $countB->assertBelow(LoginAttemptGateInterface::MAX_FAILURES);
    }

    public function testASuccessFromClientAClearsClientAsFailures(): void
    {
        for ($i = 0; $i < LoginAttemptGateInterface::MAX_FAILURES - 1; $i++) {
            $this->history->append(self::LOGIN_ID, false, self::CLIENT_IP);
        }
        $this->assertSame(
            LoginAttemptGateInterface::MAX_FAILURES - 1,
            $this->failureCount(self::LOGIN_ID, self::CLIENT_IP),
        );

        $this->history->append(self::LOGIN_ID, true, self::CLIENT_IP);
        $this->assertSame(0, $this->failureCount(self::LOGIN_ID, self::CLIENT_IP));
    }

    /** The per-client counter resets on the client's OWN success only. */
    public function testASuccessFromAnotherClientDoesNotClearClientAsCounter(): void
    {
        for ($i = 0; $i < LoginAttemptGateInterface::MAX_FAILURES; $i++) {
            $this->history->append(self::LOGIN_ID, false, self::CLIENT_IP);
        }

        $this->history->append(self::LOGIN_ID, true, '192.0.2.99');

        $this->assertSame(
            LoginAttemptGateInterface::MAX_FAILURES,
            $this->failureCount(self::LOGIN_ID, self::CLIENT_IP),
        );
    }

    /**
     * The loose account counter ignores the client: failures spread across
     * many clients (each well under MAX_FAILURES) still refuse once
     * MAX_ACCOUNT_FAILURES is crossed, from a fresh client too.
     */
    public function testFailuresAcrossClientsRefuseAtTheAccountThreshold(): void
    {
        for ($i = 0; $i < LoginAttemptGateInterface::MAX_ACCOUNT_FAILURES - 1; $i++) {
            $this->history->append(self::LOGIN_ID, false, '192.0.2.' . (100 + $i % 40));
        }
        $this->assertSame(
            LoginAttemptGateInterface::MAX_ACCOUNT_FAILURES - 1,
            $this->accountFailureCount(self::LOGIN_ID),
        );

        $this->history->append(self::LOGIN_ID, false, '192.0.2.200');
        $count = $this->gate->accountFailuresSinceLastSuccess(self::LOGIN_ID, self::WINDOW);
        $this->assertSame(LoginAttemptGateInterface::MAX_ACCOUNT_FAILURES, $count->count);
        $this->expectException(LoginAttemptsExceededException::class);
        $count->assertBelow(LoginAttemptGateInterface::MAX_ACCOUNT_FAILURES);
    }

    /** The account counter resets on any success for the loginId, whatever its client. */
    public function testAccountFailuresAreClearedByASuccessFromAnyClient(): void
    {
        $this->history->append(self::LOGIN_ID, false, self::CLIENT_IP);
        $this->history->append(self::LOGIN_ID, false, '192.0.2.99');
        $this->assertSame(2, $this->accountFailureCount(self::LOGIN_ID));

        $this->history->append(self::LOGIN_ID, true, '192.0.2.111');
        $this->assertSame(0, $this->accountFailureCount(self::LOGIN_ID));
    }

    private function failureCount(string $loginId, string $clientIp): int
    {
        return $this->gate->failuresSinceLastSuccess($loginId, $clientIp, self::WINDOW)->count;
    }

    private function accountFailureCount(string $loginId): int
    {
        return $this->gate->accountFailuresSinceLastSuccess($loginId, self::WINDOW)->count;
    }

    /** Seed one attempt `$minutesAgo` in the past — append() always stamps NOW(). */
    private function seedAttempt(string $loginId, bool $success, int $minutesAgo): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO dtb_login_history (login_history_status_id, user_name, client_ip, create_date, update_date, discriminator_type)'
            . ' VALUES (:status, :loginId, :clientIp, NOW() - INTERVAL :minutesAgo MINUTE, NOW(), \'loginhistory\')',
        );
        $statement->execute([
            'status' => $success ? 1 : 0,
            'loginId' => $loginId,
            'clientIp' => self::CLIENT_IP,
            'minutesAgo' => $minutesAgo,
        ]);
    }
}
