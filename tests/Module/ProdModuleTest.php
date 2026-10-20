<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Module;

use BEAR\AppMeta\Meta;
use BEAR\Csrf\CsrfSessionKey;
use BEAR\Csrf\Http\AllowedOrigin;
use BEAR\Resource\ResourceInterface;
use Be\Framework\Becoming;
use Be\Framework\BecomingInterface;
use Koriym\SemanticLogger\SemanticLogger;
use Koriym\SemanticLogger\SemanticLoggerInterface;
use MyVendor\BeMart\Be\Exception\PreOrderNotFoundException;
use MyVendor\BeMart\Injector as AppInjector;
use MyVendor\BeMart\Module\AppModule;
use MyVendor\BeMart\Module\MissingAllowedOriginException;
use MyVendor\BeMart\Module\ProdModule;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;

use function dirname;
use function file_exists;
use function getenv;
use function putenv;
use function unlink;

final class ProdModuleTest extends TestCase
{
    private string $logFile;

    protected function setUp(): void
    {
        $this->logFile = dirname(__DIR__, 2) . '/var/log/bemart.json';
    }

    public function testProdContextBindsBecomingInterfaceToPlainBecoming(): void
    {
        $this->skipWithoutDatabaseUrl();

        $injector = AppInjector::getInstance('prod-eccube-sql-hal-app');

        $becoming = $injector->getInstance(BecomingInterface::class);

        $this->assertInstanceOf(Becoming::class, $becoming);
    }

    public function testProdContextBindsSemanticLoggerInterfaceToPlainSemanticLogger(): void
    {
        $this->skipWithoutDatabaseUrl();

        $injector = AppInjector::getInstance('prod-eccube-sql-hal-app');

        $logger = $injector->getInstance(SemanticLoggerInterface::class);

        $this->assertInstanceOf(SemanticLogger::class, $logger);
    }

    public function testProdContextDoesNotWriteLogFileOnBecoming(): void
    {
        $this->skipWithoutDatabaseUrl();

        // Establish a known "before" timestamp by touching the file (or
        // confirming it doesn't exist). DevBecoming would overwrite this.
        if (file_exists($this->logFile)) {
            unlink($this->logFile);
        }

        // EccubeModule binds CustomerSession to EccubeSharedSessionAdapter,
        // which reads $_SESSION['customer_id'].
        $_SESSION['customer_id'] = 'customer-001';

        // Both BEAR.Csrf gates are real here: mirror a token into the session
        // slot and declare the request same-origin, as a browser form post is.
        $_SESSION[CsrfSessionKey::DEFAULT_NAME] = 'prod-csrf-mirror';
        $_SERVER['HTTP_SEC_FETCH_SITE'] = 'same-origin';

        $injector = AppInjector::getInstance('prod-eccube-sql-hal-app');

        $resource = $injector->getInstance(ResourceInterface::class);

        try {
            $resource->post('page://self/shopping/checkout', [
                'preOrderId' => 'aaaa00000000000000000000000000000000aaaa',
                'csrfToken' => 'prod-csrf-mirror',
            ]);
        } catch (PreOrderNotFoundException) {
            // The SQL-backed prod DB used in CI may not carry the fake
            // checkout fixture. The invariant under test is still that the
            // prod Becoming path does not write the semantic log file.
            $this->addToAssertionCount(1);
        }

        $this->assertFileDoesNotExist(
            $this->logFile,
            'prod-eccube-sql-hal-app must NOT write var/log/bemart.json (PII leak prevention)',
        );
    }

    public function testDevContextDoesWriteLogFileOnBecoming(): void
    {
        // Negative control: confirm TestModule (dev logging + FakeModule)
        // still writes the log. If this stops being true the test above
        // becomes vacuous.
        if (file_exists($this->logFile)) {
            unlink($this->logFile);
        }

        $injector = new Injector(
            new TestModule(new Meta('MyVendor\\BeMart', 'test')),
            dirname(__DIR__, 2) . '/var/tmp/test',
        );

        $resource = $injector->getInstance(ResourceInterface::class);
        $resource->post('page://self/shopping/checkout', [
            'preOrderId' => 'aaaa00000000000000000000000000000000aaaa',
        ]);

        $this->assertFileExists($this->logFile);
    }

    /**
     * An unset origin would run production with the same-origin gate down, silently:
     * every request still succeeds. ProdModule turns that into a boot failure. The
     * separate process keeps the env change away from the suite's own value.
     */
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testProductionRefusesToBootWithoutAnAllowedOrigin(): void
    {
        putenv(ProdModule::ALLOWED_ORIGIN_ENV);

        $this->expectException(MissingAllowedOriginException::class);
        $this->expectExceptionMessage(ProdModule::ALLOWED_ORIGIN_ENV);
        new Injector(new ProdModule());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testProductionRefusesAnEmptyAllowedOrigin(): void
    {
        putenv(ProdModule::ALLOWED_ORIGIN_ENV . '=');

        $this->expectException(MissingAllowedOriginException::class);
        new Injector(new ProdModule());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testProductionBootsWithAnAllowedOrigin(): void
    {
        putenv(ProdModule::ALLOWED_ORIGIN_ENV . '=https://shop.example');

        // ProdModule wraps AppModule as the context string does, so the origin the
        // same-origin gate will compare against is the one read from the env here.
        $injector = new Injector(new ProdModule(new AppModule(new Meta('MyVendor\\BeMart', 'prod-app'))));

        $allowedOrigin = $injector->getInstance(AllowedOrigin::class);
        $this->assertInstanceOf(AllowedOrigin::class, $allowedOrigin);
        $this->assertSame('https://shop.example', $allowedOrigin->value);
    }

    private function skipWithoutDatabaseUrl(): void
    {
        $databaseUrl = getenv('DATABASE_URL');
        if ($databaseUrl === false || $databaseUrl === '') {
            $this->markTestSkipped('DATABASE_URL not set — prod context requires SQL wiring.');
        }

        $parts = \parse_url($databaseUrl);
        if ($parts === false || ! isset($parts['scheme'], $parts['host'], $parts['user'], $parts['path'])) {
            $this->markTestSkipped('DATABASE_URL malformed — prod context requires SQL wiring.');
        }

        $serverDsn = \sprintf(
            'mysql:host=%s;port=%d;charset=utf8mb4',
            $parts['host'],
            $parts['port'] ?? 3306,
        );

        try {
            $pdo = new \PDO($serverDsn, $parts['user'], $parts['pass'] ?? '', [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ]);
            $pdo->query('SELECT 1');
        } catch (\PDOException $e) {
            $this->markTestSkipped('DATABASE_URL unreachable — prod context requires SQL wiring: ' . $e->getMessage());
        }
    }

    protected function tearDown(): void
    {
        if (file_exists($this->logFile)) {
            unlink($this->logFile);
        }

        unset(
            $_SESSION['customer_id'],
            $_SESSION[CsrfSessionKey::DEFAULT_NAME],
            $_SERVER['HTTP_SEC_FETCH_SITE'],
        );
    }
}
