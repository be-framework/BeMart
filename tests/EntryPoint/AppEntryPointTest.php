<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\EntryPoint;

use PHPUnit\Framework\TestCase;

use function dirname;
use function escapeshellarg;
use function exec;
use function file_exists;
use function file_get_contents;
use function getenv;
use function implode;
use function is_array;
use function json_decode;
use function unlink;

/** Verifies the thin CLI entrypoints and APP_CONTEXT escape hatch. */
final class AppEntryPointTest extends TestCase
{
    private string $appDir;
    private string $logFile;
    private string $stderrFile;

    protected function setUp(): void
    {
        $this->appDir = dirname(__DIR__, 2);
        $this->logFile = $this->appDir . '/var/log/bemart.json';
        $this->stderrFile = $this->appDir . '/var/tmp/test/entrypoint_stderr.log';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->stderrFile)) {
            unlink($this->stderrFile);
        }
    }

    public function testProdEntrypointDoesNotWriteLogFile(): void
    {
        $this->skipWithoutDatabaseUrl();

        if (file_exists($this->logFile)) {
            unlink($this->logFile);
        }

        $result = $this->runBin('prod.php', [
            'post',
            '/shopping/checkout?preOrderId=aaaa00000000000000000000000000000000aaaa&csrfToken=cli-smoke-token',
        ], [
            'BEMART_CLI_CUSTOMER_ID' => 'customer-001',
            'BEMART_CLI_CSRF_TOKEN' => 'cli-smoke-token',
        ]);

        $this->assertNotNull($result['json']['code'] ?? null, 'bin/prod.php must emit a response: ' . $result['stderr']);
        $this->assertGreaterThanOrEqual(400, $result['json']['code'] ?? 0);
        $this->assertFileDoesNotExist(
            $this->logFile,
            'bin/prod.php must NOT write var/log/bemart.json (PII leak prevention)',
        );
    }

    public function testProdEntrypointRejectsAnonymousCli(): void
    {
        $this->skipWithoutDatabaseUrl();

        $result = $this->runBin('prod.php', [
            'post',
            '/shopping/checkout?preOrderId=aaaa00000000000000000000000000000000aaaa&csrfToken=cli-smoke-token',
        ], ['BEMART_CLI_CSRF_TOKEN' => 'cli-smoke-token']);

        $this->assertSame(1, $result['exit'], 'bin/prod.php exits 1 on 4xx: ' . $result['stderr']);
        $this->assertGreaterThanOrEqual(400, $result['json']['code'] ?? 0);
        $this->assertLessThan(500, $result['json']['code'] ?? 0);
    }

    public function testProdEntrypointRejectsMissingCsrfTokenCli(): void
    {
        $this->skipWithoutDatabaseUrl();

        $result = $this->runBin('prod.php', [
            'post',
            '/shopping/checkout?preOrderId=aaaa00000000000000000000000000000000aaaa',
        ], ['BEMART_CLI_CUSTOMER_ID' => 'customer-001']);

        $this->assertSame(1, $result['exit'], 'bin/prod.php exits 1 on 4xx: ' . $result['stderr']);
        $this->assertSame(403, $result['json']['code'] ?? null);
    }

    public function testDevEntrypointDoesWriteLogFile(): void
    {
        if (file_exists($this->logFile)) {
            unlink($this->logFile);
        }

        $result = $this->runBin('dev.php', [
            'post',
            '/shopping/checkout?preOrderId=aaaa00000000000000000000000000000000aaaa&csrfToken=fake-csrf-token-bemart-2026',
        ]);

        $this->assertSame(0, $result['exit'], 'bin/dev.php must exit 0 on success: ' . $result['stderr']);
        $this->assertSame('cli-dev-fake-hal-api-app', $result['json']['context'] ?? null);
        $this->assertFileExists($this->logFile);
    }

    public function testAppContextEscapeHatchFailsCleanlyForUnknownContext(): void
    {
        $result = $this->runBin('app.php', ['get', '/'], ['APP_CONTEXT' => 'nope']);

        $this->assertSame(2, $result['exit']);
        $this->assertStringContainsString('Unknown APP_CONTEXT', $result['stderr']);
    }

    public function testMissingMethodAndPathFailsCleanly(): void
    {
        $result = $this->runBin('app.php', []);

        $this->assertSame(2, $result['exit']);
        $this->assertStringContainsString('Usage:', $result['stderr']);
    }

    public function testKnownPathWithUnsupportedMethodReturns404WithoutRouteFallback(): void
    {
        $result = $this->runBin('page.php', ['post', '/products/list'], ['APP_CONTEXT' => 'html-test']);

        $this->assertSame(1, $result['exit']);
        $this->assertSame(404, $result['json']['code'] ?? null);
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
            $version = (string) $pdo->query('SELECT VERSION()')->fetchColumn();
        } catch (\PDOException $e) {
            $this->markTestSkipped('DATABASE_URL unreachable — prod context requires SQL wiring: ' . $e->getMessage());
        }

    }


    /**
     * @param list<string>          $args
     * @param array<string, string> $env Extra env vars to set for the subprocess.
     *
     * @return array{exit: int, stdout: string, stderr: string, json: array<string, mixed>|null}
     */
    private function runBin(string $script, array $args, array $env = []): array
    {
        $escapedArgs = '';
        foreach ($args as $a) {
            $escapedArgs .= ' ' . escapeshellarg($a);
        }

        $databaseUrl = getenv('DATABASE_URL');
        if ($databaseUrl !== false && $databaseUrl !== '' && ! \array_key_exists('DATABASE_URL', $env)) {
            $env['DATABASE_URL'] = $databaseUrl;
        }

        $envPrefix = '';
        foreach ($env as $name => $value) {
            $envPrefix .= $name . '=' . escapeshellarg($value) . ' ';
        }

        $cmd = $envPrefix
            . 'php ' . escapeshellarg($this->appDir . '/bin/' . $script)
            . $escapedArgs
            . ' 2>' . escapeshellarg($this->stderrFile);

        $stdoutLines = [];
        $exit = 0;
        exec($cmd, $stdoutLines, $exit);
        $stdout = implode("\n", $stdoutLines);
        $stderr = file_exists($this->stderrFile)
            ? (string) file_get_contents($this->stderrFile)
            : '';

        /** @var array<string, mixed>|null $json */
        $json = null;
        if ($stdout !== '') {
            /** @var mixed $decoded */
            $decoded = json_decode($stdout, true);
            if (is_array($decoded)) {
                /** @var array<string, mixed> $decoded */
                $json = $decoded;
            }
        }

        return ['exit' => $exit, 'stdout' => $stdout, 'stderr' => $stderr, 'json' => $json];
    }
}
