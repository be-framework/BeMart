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
 * SQL-backed browser-form regression for admin news maintenance.
 *
 * The hypermedia workflow covers the canonical Resource story. This test
 * pins the actual HTML boundary: exposed form action, canonical field
 * names, POST method override redirects, and readback from rendered pages.
 */
final class HttpSqlAdminNewsFormTest extends TestCase
{
    private const HOST = '127.0.0.1:18204';
    private const ADMIN_ID = '1';
    private const CSRF_TOKEN = 'admin-news-form-csrf-token';

    private static PhpServer|null $server = null;
    private string $cookieJar;

    public static function setUpBeforeClass(): void
    {
        self::$server = new PhpServer(self::HOST, __DIR__ . '/html-sql-index.php');
        self::$server->start();
    }

    protected function setUp(): void
    {
        $this->cookieJar = (string) tempnam(sys_get_temp_dir(), 'bemart-sql-admin-news-cookie-');
    }

    public function testAdminNewsCanBeCreatedUpdatedAndDeletedThroughHtmlForms(): void
    {
        $new = $this->request('GET', '/admin/news/news');
        $this->assertSame(200, $new['status'], $new['body']);
        $this->assertStringContainsString('method="post"', $new['body']);
        $this->assertStringContainsString('action="/admin/news/news-list"', $new['body']);
        $this->assertStringContainsString('name="newsTitle"', $new['body']);
        $this->assertStringContainsString('name="publishDate"', $new['body']);
        $this->assertStringContainsString('name="newsUrl"', $new['body']);
        $this->assertStringContainsString('name="newsDescription"', $new['body']);

        $suffix = bin2hex(random_bytes(4));
        $title = 'HTTP News ' . $suffix;
        $created = $this->form('POST', '/admin/news/news-list', [
            'newsTitle' => $title,
            'publishDate' => '2027-03-01 00:00:00',
            'newsDescription' => 'Created through HTTP HTML form.',
            'newsUrl' => 'https://example.com/http-news-' . $suffix,
            'linkMethod' => '0',
            'csrfToken' => $this->csrfToken($new['body']),
        ]);

        $this->assertSame(303, $created['status'], $created['body']);
        $location = $created['headers']['Location'] ?? '';
        $this->assertStringStartsWith('/admin/news/news?newsId=', $location);
        $newsId = $this->newsIdFromLocation($location);

        $detail = $this->request('GET', $location);
        $this->assertSame(200, $detail['status'], $detail['body']);
        $this->assertStringContainsString('action="/admin/news/news?newsId=' . $newsId . '&amp;_method=put"', $detail['body']);
        $this->assertStringContainsString('value="' . $title . '"', $detail['body']);
        $this->assertStringContainsString('Created through HTTP HTML form.', $detail['body']);

        $updatedTitle = 'HTTP News Updated ' . $suffix;
        $updated = $this->form('POST', '/admin/news/news?newsId=' . $newsId . '&_method=put', [
            'newsTitle' => $updatedTitle,
            'publishDate' => '2027-03-02 00:00:00',
            'newsDescription' => 'Updated through HTTP HTML form.',
            'newsUrl' => 'https://example.com/http-news-updated-' . $suffix,
            'linkMethod' => '0',
            'csrfToken' => $this->csrfToken($detail['body']),
        ]);

        $this->assertSame(303, $updated['status'], $updated['body']);
        $this->assertSame('/admin/news/news?newsId=' . $newsId, $updated['headers']['Location'] ?? null);

        $updatedDetail = $this->request('GET', '/admin/news/news?newsId=' . $newsId);
        $this->assertSame(200, $updatedDetail['status'], $updatedDetail['body']);
        $this->assertStringContainsString('value="' . $updatedTitle . '"', $updatedDetail['body']);
        $this->assertStringContainsString('Updated through HTTP HTML form.', $updatedDetail['body']);

        $listBeforeDelete = $this->request('GET', '/admin/news/news-list');
        $this->assertSame(200, $listBeforeDelete['status'], $listBeforeDelete['body']);
        $this->assertStringContainsString($updatedTitle, $listBeforeDelete['body']);
        $this->assertStringContainsString('/admin/news/news?newsId=' . $newsId . '&_method=delete', $listBeforeDelete['body']);

        $deleted = $this->form('POST', '/admin/news/news?newsId=' . $newsId . '&_method=delete', [
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(303, $deleted['status'], $deleted['body']);
        $this->assertSame('/admin/news/news-list', $deleted['headers']['Location'] ?? null);

        $list = $this->request('GET', '/admin/news/news-list');
        $this->assertSame(200, $list['status'], $list['body']);
        $this->assertStringNotContainsString($updatedTitle, $list['body']);
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

    private function newsIdFromLocation(string $location): string
    {
        $this->assertSame(1, preg_match('/[?&]newsId=([^&]+)/', $location, $match), $location);

        return $match[1];
    }
}
