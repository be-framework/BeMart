<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Http;

use Koriym\PhpServer\PhpServer;
use PHPUnit\Framework\TestCase;

use function escapeshellarg;
use function explode;
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

/**
 * SQL-backed browser-form regression for admin holiday calendar settings.
 */
final class HttpSqlAdminCalendarFormTest extends TestCase
{
    private const HOST = '127.0.0.1:18191';
    private const ADMIN_ID = '1';
    private const CSRF_TOKEN = 'admin-calendar-form-csrf-token';

    private static PhpServer|null $server = null;
    private string $cookieJar;

    public static function setUpBeforeClass(): void
    {
        self::$server = new PhpServer(self::HOST, __DIR__ . '/html-sql-index.php');
        self::$server->start();
    }

    protected function setUp(): void
    {
        $this->cookieJar = (string) tempnam(sys_get_temp_dir(), 'bemart-sql-admin-calendar-cookie-');
    }

    public function testCalendarHolidayCanBeCreatedUpdatedAndDeletedThroughHtmlForms(): void
    {
        $page = $this->request('GET', '/admin/calendar');
        $this->assertSame(200, $page['status'], $page['body']);
        $this->assertStringContainsString('action="/admin/calendar?operation=create"', $page['body']);

        $title = 'HTTP Calendar ' . self::CSRF_TOKEN;
        $created = $this->form('POST', '/admin/calendar?operation=create', [
            'title' => $title,
            'holiday' => '2027-03-15',
            'csrfToken' => $this->csrfToken($page['body']),
        ]);

        $this->assertSame(303, $created['status'], $created['body']);
        $this->assertSame('/admin/calendar', $created['headers']['Location'] ?? null);

        $createdPage = $this->request('GET', '/admin/calendar');
        $this->assertSame(200, $createdPage['status'], $createdPage['body']);
        $this->assertStringContainsString($title, $createdPage['body']);
        $calendarId = $this->calendarIdForTitle($createdPage['body'], $title);

        $updatedTitle = $title . ' Updated';
        $updated = $this->form('POST', '/admin/calendar?operation=update', [
            'calendarId' => $calendarId,
            'title' => $updatedTitle,
            'holiday' => '2027-03-16',
            'csrfToken' => $this->csrfToken($createdPage['body']),
        ]);

        $this->assertSame(303, $updated['status'], $updated['body']);
        $this->assertSame('/admin/calendar', $updated['headers']['Location'] ?? null);

        $updatedPage = $this->request('GET', '/admin/calendar');
        $this->assertSame(200, $updatedPage['status'], $updatedPage['body']);
        $this->assertStringContainsString($updatedTitle, $updatedPage['body']);
        $this->assertStringContainsString('2027-03-16', $updatedPage['body']);

        $deleted = $this->form('POST', '/admin/calendar?calendarId=' . $calendarId . '&_method=delete', [
            'csrfToken' => $this->csrfToken($updatedPage['body']),
        ]);

        $this->assertSame(303, $deleted['status'], $deleted['body']);
        $this->assertSame('/admin/calendar', $deleted['headers']['Location'] ?? null);

        $deletedPage = $this->request('GET', '/admin/calendar');
        $this->assertSame(200, $deletedPage['status'], $deletedPage['body']);
        $this->assertStringNotContainsString($updatedTitle, $deletedPage['body']);
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

    private function calendarIdForTitle(string $body, string $title): string
    {
        $pattern = '/id="ex-calendar-([^"]+)"[^>]*>.*?' . preg_quote($title, '/') . '/s';
        $this->assertSame(1, preg_match($pattern, $body, $match), $body);

        return $match[1];
    }
}
