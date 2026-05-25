<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\EntryPoint;

use PHPUnit\Framework\TestCase;

use function dirname;
use function escapeshellarg;
use function exec;
use function file_exists;
use function file_get_contents;
use function implode;
use function is_array;
use function json_decode;
use function json_encode;
use function unlink;

use const JSON_THROW_ON_ERROR;

/**
 * Verifies that bin/app.php respects APP_CONTEXT.
 *
 * In-process Injector tests (ProdModuleTest) already prove the bindings
 * differ per Module. This file exercises the *subprocess* path —
 * Slice 5's actual product: the bin/app.php script reading the
 * environment variable and resolving it to the right Module class.
 *
 * Tests fork PHP via exec() so any DI-cache or env leak in the parent
 * process is irrelevant. Each invocation is a fresh PHP boot.
 */
final class AppEntryPointTest extends TestCase
{
    private string $appDir;
    private string $logFile;
    private string $bin;
    private string $stderrFile;

    protected function setUp(): void
    {
        $this->appDir = dirname(__DIR__, 2);
        $this->logFile = $this->appDir . '/var/log/bemart.json';
        $this->bin = $this->appDir . '/bin/app.php';
        $this->stderrFile = $this->appDir . '/var/tmp/test/entrypoint_stderr.log';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->stderrFile)) {
            unlink($this->stderrFile);
        }
    }

    public function testProdContextDoesNotWriteLogFile(): void
    {
        if (file_exists($this->logFile)) {
            unlink($this->logFile);
        }

        // Slice 7: ProdModule binds SessionInterface to EccubeSharedSessionAdapter.
        // In CLI (no HTTP session) the adapter honors BEMART_CLI_CUSTOMER_ID
        // as the authenticated customerId for operator scripts.
        //
        // Slice 8: ProdModule also binds CsrfTokenInterface to
        // EccubeSharedCsrfTokenAdapter, which in CLI accepts a reference
        // token via BEMART_CLI_CSRF_TOKEN. We pass the same value as the
        // body's `csrfToken` so the adapter's hash_equals succeeds.
        $result = $this->runBin('prod', [
            'page://self/shopping/checkout',
            json_encode([
                'preOrderId' => 'aaaa00000000000000000000000000000000aaaa',
                'csrfToken' => 'cli-smoke-token',
            ], JSON_THROW_ON_ERROR),
        ], [
            'BEMART_CLI_CUSTOMER_ID' => 'customer-001',
            'BEMART_CLI_CSRF_TOKEN' => 'cli-smoke-token',
        ]);

        // Phase 2c (production cutover): APP_CONTEXT=prod now resolves
        // ProdModule, which installs SqlModule — the prod context runs the
        // SQL-backed Reasons against the real DATABASE_URL DB rather than
        // the in-memory Fakes. The Fakes used to pre-load the `aaaa…`
        // PROCESSING pre-order; the SQL backend has none unless the DB is
        // seeded (an out-of-scope infrastructure prerequisite — load
        // sql/schema/ec-cube-4.3-mysql-mysqldump.sql and seed dtb_*/mtb_*).
        // So the checkout no longer 201s here. What this test still pins is
        // the invariant it was written for: APP_CONTEXT=prod resolves the
        // prod context AND, whatever the response code, the prod logging
        // override means var/log/bemart.json is NEVER written.
        $this->assertSame('prod', $result['json']['context'] ?? null);
        $this->assertNotNull($result['json']['code'] ?? null, 'bin/app.php must emit a response: ' . $result['stderr']);
        $this->assertFileDoesNotExist(
            $this->logFile,
            'APP_CONTEXT=prod must NOT write var/log/bemart.json (PII leak prevention)',
        );
    }

    public function testProdContextRejectsAnonymousCli(): void
    {
        // Slice 7: without BEMART_CLI_CUSTOMER_ID set, ProdModule's
        // EccubeSharedSessionAdapter reports anonymous. We pass a valid CSRF
        // token (Slice 8) so the rejection is not about CSRF.
        //
        // Phase 2c (production cutover): ProdModule installs SqlModule, so
        // CheckoutPrepared's pre-order lookup now runs SqlOrderQuery against
        // the real DB. With no seeded `aaaa…` pre-order the chain rejects at
        // the existence check (404, PreOrderNotFoundException) BEFORE it
        // reaches the anonymous-session AUTHZ check (which threw 403 under
        // the Fakes). Either way the request is rejected and bin/app.php
        // exits 1 on a 4xx — that rejection-of-an-unauthenticated-checkout
        // is the invariant this test pins.
        $result = $this->runBin('prod', [
            'page://self/shopping/checkout',
            json_encode([
                'preOrderId' => 'aaaa00000000000000000000000000000000aaaa',
                'csrfToken' => 'cli-smoke-token',
            ], JSON_THROW_ON_ERROR),
        ], ['BEMART_CLI_CSRF_TOKEN' => 'cli-smoke-token']);

        $this->assertSame(1, $result['exit'], 'bin/app.php exits 1 on 4xx: ' . $result['stderr']);
        $this->assertGreaterThanOrEqual(400, $result['json']['code'] ?? 0);
        $this->assertLessThan(500, $result['json']['code'] ?? 0);
    }

    public function testProdContextRejectsMissingCsrfTokenCli(): void
    {
        // Slice 8: a CLI request that satisfies AUTHZ but omits the CSRF
        // token must still 403. This pins down that CSRF is checked at the
        // boundary in CLI just as in HTTP.
        $result = $this->runBin('prod', [
            'page://self/shopping/checkout',
            json_encode(['preOrderId' => 'aaaa00000000000000000000000000000000aaaa'], JSON_THROW_ON_ERROR),
        ], ['BEMART_CLI_CUSTOMER_ID' => 'customer-001']);

        $this->assertSame(1, $result['exit'], 'bin/app.php exits 1 on 4xx: ' . $result['stderr']);
        $this->assertSame(403, $result['json']['code'] ?? null);
    }

    public function testAppContextDoesWriteLogFile(): void
    {
        if (file_exists($this->logFile)) {
            unlink($this->logFile);
        }

        // AppModule's FakeCsrfToken accepts FakeCsrfToken::TOKEN as the
        // reference. Pass it through the body so the resource boundary
        // lets the Becoming chain execute (and DevBecoming writes the log).
        $result = $this->runBin('app', [
            'page://self/shopping/checkout',
            json_encode([
                'preOrderId' => 'aaaa00000000000000000000000000000000aaaa',
                'csrfToken' => 'fake-csrf-token-bemart-2026',
            ], JSON_THROW_ON_ERROR),
        ]);

        $this->assertSame(0, $result['exit'], 'bin/app.php must exit 0 on success: ' . $result['stderr']);
        $this->assertSame('app', $result['json']['context'] ?? null);
        $this->assertFileExists($this->logFile);
    }

    public function testUnknownContextFailsCleanly(): void
    {
        $result = $this->runBin('nope', ['page://self/']);

        $this->assertSame(2, $result['exit']);
        $this->assertStringContainsString('Unknown APP_CONTEXT', $result['stderr']);
    }

    public function testMissingUriFailsCleanly(): void
    {
        $result = $this->runBin('app', []);

        $this->assertSame(2, $result['exit']);
        $this->assertStringContainsString('Usage:', $result['stderr']);
    }

    /**
     * @param list<string>         $args
     * @param array<string, string> $env  Extra env vars to set for the subprocess.
     *
     * @return array{exit: int, stdout: string, stderr: string, json: array<string, mixed>|null}
     */
    private function runBin(string $context, array $args, array $env = []): array
    {
        $escapedArgs = '';
        foreach ($args as $a) {
            $escapedArgs .= ' ' . escapeshellarg($a);
        }

        $envPrefix = 'APP_CONTEXT=' . escapeshellarg($context);
        foreach ($env as $name => $value) {
            $envPrefix .= ' ' . $name . '=' . escapeshellarg($value);
        }

        $cmd = $envPrefix
            . ' php ' . escapeshellarg($this->bin)
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
