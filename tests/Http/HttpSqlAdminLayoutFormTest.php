<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Http;

use Koriym\PhpServer\PhpServer;
use PHPUnit\Framework\TestCase;

use function bin2hex;
use function escapeshellarg;
use function explode;
use function http_build_query;
use function is_string;
use function preg_match;
use function preg_split;
use function random_bytes;
use function shell_exec;
use function sprintf;
use function str_contains;
use function sys_get_temp_dir;
use function tempnam;
use function trim;

/**
 * SQL-backed browser-form regression for admin layout edit.
 */
final class HttpSqlAdminLayoutFormTest extends TestCase
{
    private const HOST = '127.0.0.1:18205';
    private const ADMIN_ID = '1';
    private const CSRF_TOKEN = 'admin-layout-form-csrf-token';

    private static PhpServer|null $server = null;
    private string $cookieJar;

    public static function setUpBeforeClass(): void
    {
        self::clearCompiledContextCache();
        self::$server = new PhpServer(self::HOST, __DIR__ . '/html-sql-index.php');
        self::$server->start();
    }

    protected function setUp(): void
    {
        $this->cookieJar = (string) tempnam(sys_get_temp_dir(), 'bemart-sql-admin-layout-cookie-');
    }

    public function testAdminLayoutCanBeUpdatedThroughHtmlForm(): void
    {
        $list = $this->request('GET', '/admin/layout/layout-list');
        $this->assertSame(200, $list['status'], $list['body']);
        $this->assertSame(1, preg_match('/href="\\/admin\\/layout\\/layout\\?layoutId=([^"]+)"/', $list['body'], $match), $list['body']);
        $layoutId = $match[1];
        $this->assertIsString($layoutId);

        $edit = $this->request('GET', '/admin/layout/layout?layoutId=' . $layoutId);
        $this->assertSame(200, $edit['status'], $edit['body']);
        $this->assertStringContainsString('id="form1"', $edit['body']);
        $this->assertStringContainsString('action="/admin/layout/layout?layoutId=' . $layoutId . '&_method=put"', $edit['body']);
        $this->assertStringContainsString('name="name"', $edit['body']);
        $this->assertSame(1, preg_match('/name="name"[^>]*value="([^"]+)"/', $edit['body'], $nameMatch), $edit['body']);
        $this->assertNotSame('', $nameMatch[1]);

        $updatedName = 'HTTPレイアウト' . bin2hex(random_bytes(4));
        $updated = $this->form('POST', '/admin/layout/layout?layoutId=' . $layoutId . '&_method=put', [
            'name' => $updatedName,
            'csrfToken' => $this->csrfToken($edit['body']),
        ]);

        $this->assertSame(303, $updated['status'], $updated['body']);
        $this->assertSame('/admin/layout/layout-list', $updated['headers']['Location'] ?? null);

        $updatedList = $this->request('GET', '/admin/layout/layout-list');
        $this->assertSame(200, $updatedList['status'], $updatedList['body']);
        $this->assertStringContainsString($updatedName, $updatedList['body']);
    }

    /**
     * @param array<string, string> $fields
     *
     * @return array{status: int, headers: array<string, string>, body: string}
     */
    private function form(string $method, string $path, array $fields = []): array
    {
        $jar = escapeshellarg($this->cookieJar);
        $curl = sprintf(
            'curl -s -i -b %s -c %s -H %s -H %s -X %s -d %s %s',
            $jar,
            $jar,
            escapeshellarg('X-BeMart-Test-Admin-Id: ' . self::ADMIN_ID),
            escapeshellarg('X-BeMart-Test-Csrf-Token: ' . self::CSRF_TOKEN),
            escapeshellarg($method),
            escapeshellarg(http_build_query($fields)),
            escapeshellarg('http://' . self::HOST . $path),
        );
        $raw = shell_exec($curl);
        $this->assertIsString($raw);

        return $this->parseResponse($raw);
    }

    /** @return array{status: int, headers: array<string, string>, body: string} */
    private function request(string $method, string $path): array
    {
        $jar = escapeshellarg($this->cookieJar);
        $curl = sprintf(
            'curl -s -i -b %s -c %s -H %s -H %s -X %s %s',
            $jar,
            $jar,
            escapeshellarg('X-BeMart-Test-Admin-Id: ' . self::ADMIN_ID),
            escapeshellarg('X-BeMart-Test-Csrf-Token: ' . self::CSRF_TOKEN),
            escapeshellarg($method),
            escapeshellarg('http://' . self::HOST . $path),
        );
        $raw = shell_exec($curl);
        $this->assertIsString($raw);

        return $this->parseResponse($raw);
    }

    /** @return array{status: int, headers: array<string, string>, body: string} */
    private function parseResponse(string $raw): array
    {
        $parts = preg_split("/\r?\n\r?\n/", $raw, 2);
        $this->assertIsArray($parts);
        $headerBlock = $parts[0] ?? '';
        $body = $parts[1] ?? '';
        $this->assertIsString($headerBlock);
        $this->assertIsString($body);

        $lines = preg_split('/\r?\n/', $headerBlock) ?: [];
        $statusLine = $lines[0] ?? '';
        $this->assertIsString($statusLine);
        $this->assertSame(1, preg_match('/\s(\d{3})\s/', $statusLine, $match), $raw);

        $headers = [];
        foreach ($lines as $line) {
            if (! is_string($line) || ! str_contains($line, ':')) {
                continue;
            }

            [$name, $value] = explode(':', $line, 2);
            $headers[$name] = trim($value);
        }

        return ['status' => (int) $match[1], 'headers' => $headers, 'body' => $body];
    }

    private function csrfToken(string $body): string
    {
        $this->assertSame(1, preg_match('/name="csrfToken" value="([^"]*)"/', $body, $match), $body);

        return $match[1];
    }

    private static function clearCompiledContextCache(): void
    {
        $contextDir = dirname(__DIR__, 2) . '/var/tmp/html-eccube-sql-hal-app';
        foreach (['di', 'injector', 'twig'] as $subDir) {
            $path = $contextDir . '/' . $subDir;
            if (is_dir($path)) {
                shell_exec('rm -rf ' . escapeshellarg($path));
            }
        }
    }
}
