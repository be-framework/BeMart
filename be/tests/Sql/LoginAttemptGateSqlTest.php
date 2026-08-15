<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Sql;

use Aura\Sql\DecoratedPdo;
use BEAR\AppMeta\Meta;
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
        $injector = new Injector($module, dirname(__DIR__, 2) . '/var/tmp/test');

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
        $this->assertSame(0, $this->failureCount(self::LOGIN_ID));

        $this->history->append(self::LOGIN_ID, false, self::CLIENT_IP);
        $this->history->append(self::LOGIN_ID, false, self::CLIENT_IP);
        $this->assertSame(2, $this->failureCount(self::LOGIN_ID));

        $this->history->append(self::LOGIN_ID, true, self::CLIENT_IP);
        $this->assertSame(0, $this->failureCount(self::LOGIN_ID));

        // Same second as the success: ordering is by id, not by the
        // second-granularity create_date.
        $this->history->append(self::LOGIN_ID, false, self::CLIENT_IP);
        $this->assertSame(1, $this->failureCount(self::LOGIN_ID));
    }

    public function testFailuresOlderThanTheWindowAreNotCounted(): void
    {
        $this->seedAttempt(self::LOGIN_ID, false, self::WINDOW + 1);
        $this->assertSame(0, $this->failureCount(self::LOGIN_ID));

        $this->seedAttempt(self::LOGIN_ID, false, self::WINDOW - 1);
        $this->assertSame(1, $this->failureCount(self::LOGIN_ID));
    }

    /** A success that has already aged out must not clear in-window failures. */
    public function testSuccessOlderThanTheWindowDoesNotClearFailures(): void
    {
        $this->seedAttempt(self::LOGIN_ID, true, self::WINDOW + 1);
        $this->seedAttempt(self::LOGIN_ID, false, self::WINDOW - 1);

        $this->assertSame(1, $this->failureCount(self::LOGIN_ID));
    }

    public function testFailuresAreCountedPerLoginId(): void
    {
        $this->history->append('other-admin', false, self::CLIENT_IP);
        $this->history->append('other-admin', false, self::CLIENT_IP);

        $this->assertSame(0, $this->failureCount(self::LOGIN_ID));
        $this->assertSame(2, $this->failureCount('other-admin'));
    }

    private function failureCount(string $loginId): int
    {
        return $this->gate->failuresSinceLastSuccess($loginId, self::WINDOW)->count;
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
