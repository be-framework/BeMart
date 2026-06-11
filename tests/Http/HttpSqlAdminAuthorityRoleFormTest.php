<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Http;

use Koriym\PhpServer\PhpServer;
use PHPUnit\Framework\TestCase;

use function bin2hex;
use function dirname;
use function escapeshellarg;
use function explode;
use function http_build_query;
use function is_dir;
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
 * SQL-backed browser-form regression for admin authority URL-deny rules.
 */
final class HttpSqlAdminAuthorityRoleFormTest extends TestCase
{
    private const HOST = '127.0.0.1:18207';
    private const ADMIN_ID = 'ad000000000000000000000000000001';
    private const CSRF_TOKEN = 'admin-authority-role-form-csrf-token';

    private static PhpServer|null $server = null;
    private string $cookieJar;
    private string $denyUrl;

    public static function setUpBeforeClass(): void
    {
        self::clearCompiledContextCache();
        self::$server = new PhpServer(self::HOST, __DIR__ . '/html-sql-index.php');
        self::$server->start();
    }

    protected function setUp(): void
    {
        $this->cookieJar = (string) tempnam(sys_get_temp_dir(), 'bemart-sql-admin-authority-role-cookie-');
        $this->denyUrl = '/admin/http-deny-' . bin2hex(random_bytes(4));
    }

    public function testAuthorityRoleHtmlFormPersistsUrlDenyRule(): void
    {
        $page = $this->request('GET', '/admin/authority-role');
        $this->assertSame(200, $page['status'], $page['body']);
        $this->assertStringContainsString('name="form1"', $page['body']);
        $this->assertStringContainsString('name="AuthorityRoles[0][Authority]"', $page['body']);
        $this->assertStringContainsString('name="AuthorityRoles[0][deny_url]"', $page['body']);
        $this->assertStringContainsString('value="' . self::CSRF_TOKEN . '"', $page['body']);

        $response = $this->form('POST', $this->formAction($page['body']), [
            'AuthorityRoles[0][Authority]' => '1',
            'AuthorityRoles[0][deny_url]' => $this->denyUrl,
            'csrfToken' => $this->csrfToken($page['body']),
        ]);

        $this->assertSame(303, $response['status'], $response['body']);
        $this->assertSame('/admin/authority-role', $response['headers']['Location'] ?? null);

        $readback = $this->request('GET', '/admin/authority-role');
        $this->assertSame(200, $readback['status'], $readback['body']);
        $this->assertStringContainsString('value="' . $this->denyUrl . '"', $readback['body']);
    }

    /** @return array{status: int, headers: array<string, string>, body: string} */
    private function request(string $method, string $path): array
    {
        $jar = escapeshellarg($this->cookieJar);
        $curl = sprintf(
            'curl --max-time 15 -s -i -b %s -c %s -H %s -H %s -X %s %s',
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

    /**
     * @param array<string, mixed> $fields
     *
     * @return array{status: int, headers: array<string, string>, body: string}
     */
    private function form(string $method, string $path, array $fields): array
    {
        $jar = escapeshellarg($this->cookieJar);
        $curl = sprintf(
            'curl --max-time 15 -s -i -b %s -c %s -H %s -H %s -X %s -d %s %s',
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

    private function formAction(string $body): string
    {
        $this->assertSame(1, preg_match('/<form name="form1" method="post" action="([^"]+)"/', $body, $match), $body);

        return $match[1];
    }

    private function csrfToken(string $body): string
    {
        $this->assertSame(1, preg_match('/name="csrfToken" value="([^"]*)"/', $body, $match), $body);

        return $match[1];
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
