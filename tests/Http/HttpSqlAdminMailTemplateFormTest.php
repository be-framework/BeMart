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
use function preg_quote;
use function preg_split;
use function random_bytes;
use function shell_exec;
use function sprintf;
use function str_contains;
use function sys_get_temp_dir;
use function tempnam;
use function trim;

/**
 * SQL-backed browser-form regression for admin mail-template maintenance.
 *
 * Hypermedia already proves the Resource choreography. This test pins the
 * actual admin HTML boundary: the selected-template page populates the edit
 * form, the browser posts `mail_subject`, and delete follows the rendered
 * `_method=delete` affordance.
 */
final class HttpSqlAdminMailTemplateFormTest extends TestCase
{
    private const HOST = '127.0.0.1:18203';
    private const ADMIN_ID = 'ad000000000000000000000000000001';
    private const CSRF_TOKEN = 'admin-mail-template-form-csrf-token';

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
        $this->cookieJar = (string) tempnam(sys_get_temp_dir(), 'bemart-sql-admin-mail-cookie-');
    }

    public function testMailTemplateHtmlFormUpdatesAndDeletesCreatedTemplate(): void
    {
        $list = $this->request('GET', '/admin/mail-template');
        $this->assertSame(200, $list['status'], $list['body']);
        $this->assertStringContainsString('id="form1"', $list['body']);
        $this->assertStringContainsString('action="/admin/mail-template"', $list['body']);
        $this->assertStringContainsString('name="csrfToken"', $list['body']);
        $this->assertStringContainsString('value="' . self::CSRF_TOKEN . '"', $list['body']);

        $templateName = 'HTTP Mail Template ' . bin2hex(random_bytes(4));
        $created = $this->form('POST', '/admin/mail-template/create', [
            'mailTemplateName' => $templateName,
            'fileName' => 'Mail/http-' . bin2hex(random_bytes(4)) . '.twig',
            'mailSubject' => 'Initial HTTP subject',
            'csrfToken' => $this->csrfToken($list['body']),
        ]);
        $this->assertSame(201, $created['status'], $created['body']);
        $this->assertSame('/admin/mail-template', $created['headers']['Location'] ?? null);

        $afterCreate = $this->request('GET', '/admin/mail-template');
        $this->assertSame(200, $afterCreate['status'], $afterCreate['body']);
        $mailTemplateId = $this->mailTemplateIdFromList($afterCreate['body'], $templateName);
        $edit = $this->request('GET', '/admin/mail-template?mailTemplateId=' . $mailTemplateId);
        $this->assertSame(200, $edit['status'], $edit['body']);
        $this->assertStringContainsString($templateName, $edit['body']);
        $this->assertStringContainsString('name="mailTemplateId"', $edit['body']);
        $this->assertStringContainsString('value="' . $mailTemplateId . '"', $edit['body']);
        $this->assertStringContainsString('name="mail_subject"', $edit['body']);
        $this->assertStringContainsString('value="Initial HTTP subject"', $edit['body']);
        $this->assertStringContainsString('/admin/mail-template?mailTemplateId=' . $mailTemplateId . '&_method=delete', $edit['body']);

        $updatedSubject = 'Updated HTTP subject';
        $updated = $this->form('POST', '/admin/mail-template', [
            'mailTemplateId' => (string) $mailTemplateId,
            'mail_subject' => $updatedSubject,
            'csrfToken' => $this->csrfToken($edit['body']),
        ]);
        $this->assertSame(303, $updated['status'], $updated['body']);
        $this->assertSame('/admin/mail-template?mailTemplateId=' . $mailTemplateId, $updated['headers']['Location'] ?? null);

        $afterUpdate = $this->request('GET', '/admin/mail-template?mailTemplateId=' . $mailTemplateId);
        $this->assertSame(200, $afterUpdate['status'], $afterUpdate['body']);
        $this->assertStringContainsString('value="' . $updatedSubject . '"', $afterUpdate['body']);

        $deleted = $this->form('POST', '/admin/mail-template?mailTemplateId=' . $mailTemplateId . '&_method=delete', [
            'csrfToken' => $this->csrfToken($afterUpdate['body']),
        ]);
        $this->assertSame(303, $deleted['status'], $deleted['body']);
        $this->assertSame('/admin/mail-template', $deleted['headers']['Location'] ?? null);

        $afterDelete = $this->request('GET', '/admin/mail-template');
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

    /**
     * @param array<string, mixed> $fields
     *
     * @return array{status: int, headers: array<string, string>, body: string}
     */
    private function form(string $method, string $path, array $fields): array
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

    private function csrfToken(string $body): string
    {
        $this->assertSame(1, preg_match('/name="csrfToken" value="([^"]*)"/', $body, $match), $body);

        return $match[1];
    }

    private function mailTemplateIdFromList(string $body, string $templateName): int
    {
        $this->assertSame(
            1,
            preg_match('/<option value="(\\d+)">\\s*' . preg_quote($templateName, '/') . '\\s*<\\/option>/s', $body, $match),
            $body,
        );

        return (int) $match[1];
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
