<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Http;

use Koriym\PhpServer\PhpServer;
use PHPUnit\Framework\TestCase;

use function array_map;
use function bin2hex;
use function dirname;
use function escapeshellarg;
use function explode;
use function file_put_contents;
use function implode;
use function is_dir;
use function is_string;
use function preg_match;
use function preg_quote;
use function preg_split;
use function random_bytes;
use function shell_exec;
use function sprintf;
use function str_contains;
use function sys_get_temp_dir;
use function tempnam;
use function trim;
use function unlink;

/**
 * SQL-backed browser-form regression for admin template uploads.
 *
 * The workflow posts the BEAR-standard `FileUpload` value. This test pins
 * the actual browser boundary: multipart/form-data, same CSRF/session, PRG,
 * and list readback from SQL.
 */
final class HttpSqlAdminTemplateFormTest extends TestCase
{
    private const HOST = '127.0.0.1:18193';
    private const ADMIN_ID = 'ad000000000000000000000000000001';
    private const CSRF_TOKEN = 'admin-template-upload-csrf-token';

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
        $this->cookieJar = (string) tempnam(sys_get_temp_dir(), 'bemart-sql-admin-template-cookie-');
    }

    public function testTemplateMultipartUploadCreatesTemplateAndRedirectsToList(): void
    {
        $form = $this->request('GET', '/admin/template/template-add');
        $this->assertSame(200, $form['status'], $form['body']);
        $this->assertStringContainsString('enctype="multipart/form-data"', $form['body']);
        $this->assertStringContainsString('name="templateCode"', $form['body']);
        $this->assertStringContainsString('name="templateName"', $form['body']);
        $this->assertStringContainsString('name="file"', $form['body']);
        $this->assertStringContainsString('value="' . self::CSRF_TOKEN . '"', $form['body']);

        $templateCode = 'tplupload' . bin2hex(random_bytes(4));
        $templateName = 'Template Upload ' . bin2hex(random_bytes(3));
        $uploaded = $this->multipartUpload($templateCode, $templateName);

        $this->assertSame(303, $uploaded['status'], $uploaded['body']);
        $this->assertSame('/admin/template/template-list', $uploaded['headers']['Location'] ?? null);

        $list = $this->request('GET', '/admin/template/template-list');
        $this->assertSame(200, $list['status'], $list['body']);
        $this->assertStringContainsString($templateName, $list['body']);
        $this->assertSame(
            1,
            preg_match('/<tr\s[^>]*id="row-([^"]+)"[^>]*>(?:(?!<\/tr>).)*?' . preg_quote($templateName, '/') . '/s', $list['body'], $match),
            $list['body'],
        );
        $templateId = $match[1];

        $selected = $this->formPost('/admin/template/template-list?_method=put', ['templateId' => $templateId]);
        $this->assertSame(303, $selected['status'], $selected['body']);
        $this->assertSame('/admin/template/template-list', $selected['headers']['Location'] ?? null);

        $afterSelect = $this->request('GET', '/admin/template/template-list');
        $this->assertSame(200, $afterSelect['status'], $afterSelect['body']);
        $this->assertSame(
            1,
            preg_match('/<tr\s[^>]*id="row-' . preg_quote($templateId, '/') . '"[^>]*>(?:(?!<\/tr>).)*?checked(?:(?!<\/tr>).)*?' . preg_quote($templateName, '/') . '/s', $afterSelect['body']),
            $afterSelect['body'],
        );

        $deleted = $this->formPost('/admin/template/template-list?_method=delete', ['templateId' => $templateId]);
        $this->assertSame(303, $deleted['status'], $deleted['body']);
        $this->assertSame('/admin/template/template-list', $deleted['headers']['Location'] ?? null);

        $afterDelete = $this->request('GET', '/admin/template/template-list');
        $this->assertSame(200, $afterDelete['status'], $afterDelete['body']);
        $this->assertStringNotContainsString($templateName, $afterDelete['body']);
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
    private function multipartUpload(string $templateCode, string $templateName): array
    {
        $file = (string) tempnam(sys_get_temp_dir(), 'bemart-template-upload-');
        file_put_contents($file, "PK_FAKE_ZIP\nTemplate upload fixture\n");

        $jar = escapeshellarg($this->cookieJar);
        $curl = sprintf(
            'curl -s -i -b %s -c %s -H %s -H %s -F %s -F %s -F %s -F %s %s',
            $jar,
            $jar,
            escapeshellarg('X-BeMart-Test-Admin-Id: ' . self::ADMIN_ID),
            escapeshellarg('X-BeMart-Test-Csrf-Token: ' . self::CSRF_TOKEN),
            escapeshellarg('csrfToken=' . self::CSRF_TOKEN),
            escapeshellarg('templateCode=' . $templateCode),
            escapeshellarg('templateName=' . $templateName),
            escapeshellarg('file=@' . $file . ';type=application/zip;filename=' . $templateCode . '.zip'),
            escapeshellarg('http://' . self::HOST . '/admin/template/template-add'),
        );
        $raw = shell_exec($curl);
        unlink($file);
        $this->assertIsString($raw);

        return $this->parseResponse($raw);
    }

    /**
     * @param array<string, string> $fields
     * @return array{status: int, headers: array<string, string>, body: string}
     */
    private function formPost(string $path, array $fields): array
    {
        $jar = escapeshellarg($this->cookieJar);
        $fieldArgs = [escapeshellarg('csrfToken=' . self::CSRF_TOKEN)];
        foreach ($fields as $name => $value) {
            $fieldArgs[] = escapeshellarg($name . '=' . $value);
        }

        $curl = sprintf(
            'curl -s -i -b %s -c %s -H %s -H %s %s %s',
            $jar,
            $jar,
            escapeshellarg('X-BeMart-Test-Admin-Id: ' . self::ADMIN_ID),
            escapeshellarg('X-BeMart-Test-Csrf-Token: ' . self::CSRF_TOKEN),
            implode(' ', array_map(static fn (string $field): string => '-F ' . $field, $fieldArgs)),
            escapeshellarg('http://' . self::HOST . $path),
        );
        $raw = shell_exec($curl);
        $this->assertIsString($raw);

        return $this->parseResponse($raw);
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
        $this->assertSame(1, preg_match('/\s(\d{3})\s/', $statusLine, $match));

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
}
