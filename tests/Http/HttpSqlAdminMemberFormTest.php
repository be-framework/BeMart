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
 * SQL-backed browser-form regression for admin member maintenance.
 *
 * Hypermedia covers the Resource story. This test pins the real HTML form
 * boundary: form action, method override, submitted fields, redirect, and
 * readback from the rendered admin pages.
 */
final class HttpSqlAdminMemberFormTest extends TestCase
{
    private const HOST = '127.0.0.1:18188';
    private const ADMIN_ID = '1';
    private const CSRF_TOKEN = 'admin-member-form-csrf-token';
    private const PASSWORD = 'admin-member-form-password-2026';

    private static PhpServer|null $server = null;
    private string $cookieJar;

    public static function setUpBeforeClass(): void
    {
        self::$server = new PhpServer(self::HOST, __DIR__ . '/html-sql-index.php');
        self::$server->start();
    }

    protected function setUp(): void
    {
        $this->cookieJar = (string) tempnam(sys_get_temp_dir(), 'bemart-sql-admin-member-cookie-');
    }

    public function testAdminMemberCanBeCreatedUpdatedDeletedThroughHtmlForm(): void
    {
        $new = $this->request('GET', '/admin/member');
        $this->assertSame(200, $new['status'], $new['body']);
        $this->assertStringContainsString('id="member_form"', $new['body']);
        $this->assertStringContainsString('action="/admin/member"', $new['body']);

        $loginId = 'http-member-' . bin2hex(random_bytes(4));
        $name = 'HTTP管理者';
        $created = $this->form('POST', '/admin/member', [
            'name' => $name,
            'loginId' => $loginId,
            'password' => self::PASSWORD,
            'passwordConfirm' => self::PASSWORD,
            'authority' => '1',
            'mode' => 'member_form',
            'csrfToken' => $this->csrfToken($new['body']),
        ]);

        $this->assertSame(303, $created['status'], $created['body']);
        $this->assertSame('/admin/member?loginId=' . $loginId, $created['headers']['Location'] ?? null);

        $detail = $this->request('GET', '/admin/member?loginId=' . $loginId);
        $this->assertSame(200, $detail['status'], $detail['body']);
        $this->assertStringContainsString('value="' . $loginId . '"', $detail['body']);
        $this->assertStringContainsString('value="' . $name . '"', $detail['body']);
        $this->assertStringContainsString('_method=put', $detail['body']);
        $this->assertStringNotContainsString('name="password"', $detail['body']);
        $this->assertStringNotContainsString('name="passwordConfirm"', $detail['body']);

        $updatedName = 'HTTP管理者更新';
        $updated = $this->form('POST', '/admin/member?loginId=' . $loginId . '&_method=put', [
            'name' => $updatedName,
            'loginId' => $loginId,
            'mode' => 'member_form',
            'csrfToken' => $this->csrfToken($detail['body']),
        ]);

        $this->assertSame(303, $updated['status'], $updated['body']);
        $this->assertSame('/admin/member?loginId=' . $loginId, $updated['headers']['Location'] ?? null);

        $updatedDetail = $this->request('GET', '/admin/member?loginId=' . $loginId);
        $this->assertSame(200, $updatedDetail['status'], $updatedDetail['body']);
        $this->assertStringContainsString('value="' . $updatedName . '"', $updatedDetail['body']);

        $listQuery = http_build_query(['nameKeyword' => $updatedName]);
        $listBeforeDelete = $this->request('GET', '/admin/member-list?' . $listQuery);
        $this->assertSame(200, $listBeforeDelete['status'], $listBeforeDelete['body']);
        $this->assertStringContainsString('/admin/member?loginId=' . $loginId . '&_method=delete', $listBeforeDelete['body']);
        $deleted = $this->form('POST', '/admin/member?loginId=' . $loginId . '&_method=delete', [
            'mode' => 'member_form',
            'csrfToken' => $this->csrfToken($listBeforeDelete['body']),
        ]);

        $this->assertSame(303, $deleted['status'], $deleted['body']);
        $this->assertSame('/admin/member-list', $deleted['headers']['Location'] ?? null);

        $list = $this->request('GET', '/admin/member-list?' . $listQuery);
        $this->assertSame(200, $list['status'], $list['body']);
        $this->assertStringContainsString($updatedName, $list['body']);
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
}
