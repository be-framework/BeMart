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
 * SQL-backed browser-form regression for admin block create/update/delete.
 */
final class HttpSqlAdminBlockFormTest extends TestCase
{
    private const HOST = '127.0.0.1:18206';
    private const ADMIN_ID = '1';
    private const CSRF_TOKEN = 'admin-block-form-csrf-token';

    private static PhpServer|null $server = null;
    private string $cookieJar;

    public static function setUpBeforeClass(): void
    {
        self::$server = new PhpServer(self::HOST, __DIR__ . '/html-sql-index.php');
        self::$server->start();
    }

    protected function setUp(): void
    {
        $this->cookieJar = (string) tempnam(sys_get_temp_dir(), 'bemart-sql-admin-block-cookie-');
    }

    public function testAdminBlockCanBeCreatedAndDeletedThroughHtmlForms(): void
    {
        $new = $this->request('GET', '/admin/block/block');
        $this->assertSame(200, $new['status'], $new['body']);
        $this->assertStringContainsString('id="content_block_form"', $new['body']);
        $this->assertStringContainsString('action="/admin/block/block-list"', $new['body']);
        $this->assertStringContainsString('name="blockName"', $new['body']);
        $this->assertStringContainsString('name="blockFileName"', $new['body']);
        $this->assertStringContainsString('id="block_block_html"', $new['body']);
        $this->assertStringContainsString('disabled="disabled"', $new['body']);

        $suffix = bin2hex(random_bytes(4));
        $blockName = 'HTTPブロック' . $suffix;
        $blockFileName = 'http_block_' . $suffix;
        $created = $this->form('POST', '/admin/block/block-list', [
            'blockName' => $blockName,
            'blockFileName' => $blockFileName,
            'csrfToken' => $this->csrfToken($new['body']),
        ]);

        $this->assertSame(303, $created['status'], $created['body']);
        $location = $created['headers']['Location'] ?? '';
        $this->assertStringStartsWith('/admin/block/block?blockId=', $location);
        $blockId = $this->blockIdFromLocation($location);

        $edit = $this->request('GET', $location);
        $this->assertSame(200, $edit['status'], $edit['body']);
        $this->assertStringContainsString('action="/admin/block/block?blockId=' . $blockId . '&_method=put"', $edit['body']);
        $this->assertStringContainsString('name="blockName"', $edit['body']);
        $this->assertStringContainsString('value="' . $blockName . '"', $edit['body']);
        $this->assertStringContainsString('name="blockFileName"', $edit['body']);
        $this->assertStringContainsString('value="' . $blockFileName . '"', $edit['body']);

        $updatedBlockName = $blockName . '更新';
        $updatedBlockFileName = $blockFileName . '_updated';
        $updated = $this->form('POST', '/admin/block/block?blockId=' . $blockId . '&_method=put', [
            'blockName' => $updatedBlockName,
            'blockFileName' => $updatedBlockFileName,
            'csrfToken' => $this->csrfToken($edit['body']),
        ]);

        $this->assertSame(303, $updated['status'], $updated['body']);
        $this->assertSame('/admin/block/block-list', $updated['headers']['Location'] ?? null);

        $listBeforeDelete = $this->request('GET', '/admin/block/block-list');
        $this->assertSame(200, $listBeforeDelete['status'], $listBeforeDelete['body']);
        $this->assertStringContainsString($updatedBlockName, $listBeforeDelete['body']);
        $this->assertStringContainsString($updatedBlockFileName . '.twig', $listBeforeDelete['body']);
        $this->assertStringContainsString('/admin/block/block?blockId=' . $blockId . '&_method=delete', $listBeforeDelete['body']);

        $deleted = $this->form('POST', '/admin/block/block?blockId=' . $blockId . '&_method=delete', [
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(303, $deleted['status'], $deleted['body']);
        $this->assertSame('/admin/block/block-list', $deleted['headers']['Location'] ?? null);

        $list = $this->request('GET', '/admin/block/block-list');
        $this->assertSame(200, $list['status'], $list['body']);
        $this->assertStringNotContainsString($updatedBlockName, $list['body']);
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

    private function blockIdFromLocation(string $location): string
    {
        $this->assertSame(1, preg_match('/[?&]blockId=([^&]+)/', $location, $match), $location);

        return $match[1];
    }
}
