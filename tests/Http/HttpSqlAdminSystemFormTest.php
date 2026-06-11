<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Http;

use Koriym\PhpServer\PhpServer;
use PHPUnit\Framework\TestCase;

use function escapeshellarg;
use function explode;
use function file_exists;
use function http_build_query;
use function is_string;
use function preg_match;
use function preg_split;
use function shell_exec;
use function sprintf;
use function str_contains;
use function sys_get_temp_dir;
use function tempnam;
use function trim;
use function unlink;

/**
 * SQL-backed browser-form regressions for admin system settings.
 */
final class HttpSqlAdminSystemFormTest extends TestCase
{
    private const HOST = '127.0.0.1:18190';
    private const ADMIN_ID = '1';
    private const CSRF_TOKEN = 'admin-system-form-csrf-token';

    private static PhpServer|null $server = null;
    private string $cookieJar;

    public static function setUpBeforeClass(): void
    {
        self::removeSecurityState();
        self::$server = new PhpServer(self::HOST, __DIR__ . '/html-sql-index.php');
        self::$server->start();
    }

    public static function tearDownAfterClass(): void
    {
        self::removeSecurityState();
    }

    protected function setUp(): void
    {
        $this->cookieJar = (string) tempnam(sys_get_temp_dir(), 'bemart-sql-admin-system-cookie-');
    }

    public function testSecuritySettingsCanBeUpdatedAndReadBackThroughHtmlForm(): void
    {
        $page = $this->request('GET', '/admin/security');
        $this->assertSame(200, $page['status'], $page['body']);
        $this->assertStringContainsString('action="/admin/security?_method=put"', $page['body']);

        $trustedHosts = '^http-system-form\.test$';
        $updated = $this->form('POST', '/admin/security?_method=put', [
            'adminRouteDir' => 'admin',
            'adminAllowHosts' => '',
            'adminDenyHosts' => '',
            'frontAllowHosts' => '',
            'frontDenyHosts' => '',
            'trustedHosts' => $trustedHosts,
            'csrfToken' => $this->csrfToken($page['body']),
        ]);

        $this->assertSame(200, $updated['status'], $updated['body']);
        $this->assertSame('/admin/security', $updated['headers']['Location'] ?? null);

        $readback = $this->request('GET', '/admin/security');
        $this->assertSame(200, $readback['status'], $readback['body']);
        $this->assertStringContainsString('value="' . $trustedHosts . '"', $readback['body']);
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

    private static function removeSecurityState(): void
    {
        $file = __DIR__ . '/../../var/tmp/security-config.json';
        if (file_exists($file)) {
            unlink($file);
        }
    }
}
