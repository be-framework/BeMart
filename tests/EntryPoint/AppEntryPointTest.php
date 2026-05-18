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

        $result = $this->runBin('prod', [
            'page://self/shopping/checkout',
            json_encode(['preOrderId' => 'aaaa00000000000000000000000000000000aaaa'], JSON_THROW_ON_ERROR),
        ]);

        $this->assertSame(0, $result['exit'], 'bin/app.php must exit 0 on success: ' . $result['stderr']);
        $this->assertSame('prod', $result['json']['context'] ?? null);
        $this->assertSame(201, $result['json']['code'] ?? null);
        $this->assertFileDoesNotExist(
            $this->logFile,
            'APP_CONTEXT=prod must NOT write var/log/bemart.json (PII leak prevention)',
        );
    }

    public function testAppContextDoesWriteLogFile(): void
    {
        if (file_exists($this->logFile)) {
            unlink($this->logFile);
        }

        $result = $this->runBin('app', [
            'page://self/shopping/checkout',
            json_encode(['preOrderId' => 'aaaa00000000000000000000000000000000aaaa'], JSON_THROW_ON_ERROR),
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
     * @param list<string> $args
     *
     * @return array{exit: int, stdout: string, stderr: string, json: array<string, mixed>|null}
     */
    private function runBin(string $context, array $args): array
    {
        $escapedArgs = '';
        foreach ($args as $a) {
            $escapedArgs .= ' ' . escapeshellarg($a);
        }

        $cmd = 'APP_CONTEXT=' . escapeshellarg($context)
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
