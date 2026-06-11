<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Http;

use Koriym\PhpServer\PhpServer;
use PHPUnit\Framework\TestCase;

use function dirname;
use function escapeshellarg;
use function explode;
use function file_exists;
use function glob;
use function http_build_query;
use function is_array;
use function is_file;
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
 * SQL-backed browser-form regression for admin operational content pages.
 *
 * These operations are fake/noop boundaries in BeMart, but the browser still
 * needs real form affordances: action, method override, CSRF, redirect, and
 * readback from the rendered admin page.
 */
final class HttpSqlAdminContentOperationFormTest extends TestCase
{
    private const HOST = '127.0.0.1:18189';
    private const ADMIN_ID = '1';
    private const CSRF_TOKEN = 'admin-content-operation-csrf-token';

    private static PhpServer|null $server = null;
    private string $cookieJar;

    public static function setUpBeforeClass(): void
    {
        self::removeMaintenanceFlag();
        self::removeCustomizeAssetState();
        self::$server = new PhpServer(self::HOST, __DIR__ . '/html-sql-index.php');
        self::$server->start();
    }

    public static function tearDownAfterClass(): void
    {
        self::removeMaintenanceFlag();
        self::removeCustomizeAssetState();
    }

    protected function setUp(): void
    {
        $this->cookieJar = (string) tempnam(sys_get_temp_dir(), 'bemart-sql-admin-content-cookie-');
    }

    public function testCacheCanBeClearedThroughHtmlForm(): void
    {
        $page = $this->request('GET', '/admin/content/cache');
        $this->assertSame(200, $page['status'], $page['body']);
        $this->assertStringContainsString('action="/admin/content/cache?_method=put"', $page['body']);

        $cleared = $this->form('POST', '/admin/content/cache?_method=put', [
            'mode' => 'content_operation_form',
            'csrfToken' => $this->csrfToken($page['body']),
        ]);

        $this->assertSame(303, $cleared['status'], $cleared['body']);
        $this->assertSame('/admin/content/cache', $cleared['headers']['Location'] ?? null);

        $readback = $this->request('GET', '/admin/content/cache');
        $this->assertSame(200, $readback['status'], $readback['body']);
        $this->assertStringContainsString('キャッシュ管理', $readback['body']);
    }

    public function testMaintenanceCanBeEnabledAndDisabledThroughHtmlForm(): void
    {
        $page = $this->request('GET', '/admin/content/maintenance');
        $this->assertSame(200, $page['status'], $page['body']);
        $this->assertStringContainsString('action="/admin/content/maintenance?_method=put"', $page['body']);

        $enabled = $this->form('POST', '/admin/content/maintenance?_method=put', [
            'enabled' => '1',
            'mode' => 'content_operation_form',
            'csrfToken' => $this->csrfToken($page['body']),
        ]);

        $this->assertSame(303, $enabled['status'], $enabled['body']);
        $this->assertSame('/admin/content/maintenance', $enabled['headers']['Location'] ?? null);

        $enabledPage = $this->request('GET', '/admin/content/maintenance');
        $this->assertSame(200, $enabledPage['status'], $enabledPage['body']);
        $this->assertStringContainsString('無効にする', $enabledPage['body']);

        $disabled = $this->form('POST', '/admin/content/maintenance?_method=put', [
            'enabled' => '0',
            'mode' => 'content_operation_form',
            'csrfToken' => $this->csrfToken($enabledPage['body']),
        ]);

        $this->assertSame(303, $disabled['status'], $disabled['body']);
        $this->assertSame('/admin/content/maintenance', $disabled['headers']['Location'] ?? null);

        $disabledPage = $this->request('GET', '/admin/content/maintenance');
        $this->assertSame(200, $disabledPage['status'], $disabledPage['body']);
        $this->assertStringContainsString('有効にする', $disabledPage['body']);
    }

    public function testCssCanBeUpdatedThroughHtmlForm(): void
    {
        $page = $this->request('GET', '/admin/content/css');
        $this->assertSame(200, $page['status'], $page['body']);
        $this->assertStringContainsString('action="/admin/content/css?_method=put"', $page['body']);
        $this->assertStringContainsString('name="css"', $page['body']);

        $css = "/* bemart-css-readback */\n.bemart-css-readback { color: #123456; }\n";
        $updated = $this->form('POST', '/admin/content/css?_method=put', [
            'css' => $css,
            'mode' => 'content_operation_form',
            'csrfToken' => $this->csrfToken($page['body']),
        ]);

        $this->assertSame(303, $updated['status'], $updated['body']);
        $this->assertSame('/admin/content/css', $updated['headers']['Location'] ?? null);

        $readback = $this->request('GET', '/admin/content/css');
        $this->assertSame(200, $readback['status'], $readback['body']);
        $this->assertStringContainsString('bemart-css-readback', $readback['body']);
    }

    public function testJsCanBeUpdatedThroughHtmlForm(): void
    {
        $page = $this->request('GET', '/admin/content/js');
        $this->assertSame(200, $page['status'], $page['body']);
        $this->assertStringContainsString('action="/admin/content/js?_method=put"', $page['body']);
        $this->assertStringContainsString('name="js"', $page['body']);

        $js = "window.bemartJsReadback = 'bemart-js-readback';\n";
        $updated = $this->form('POST', '/admin/content/js?_method=put', [
            'js' => $js,
            'mode' => 'content_operation_form',
            'csrfToken' => $this->csrfToken($page['body']),
        ]);

        $this->assertSame(303, $updated['status'], $updated['body']);
        $this->assertSame('/admin/content/js', $updated['headers']['Location'] ?? null);

        $readback = $this->request('GET', '/admin/content/js');
        $this->assertSame(200, $readback['status'], $readback['body']);
        $this->assertStringContainsString('bemart-js-readback', $readback['body']);
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

    private static function removeMaintenanceFlag(): void
    {
        $flag = __DIR__ . '/../../var/tmp/maintenance-mode.flag';
        if (file_exists($flag)) {
            unlink($flag);
        }
    }

    private static function removeCustomizeAssetState(): void
    {
        $files = glob(dirname(__DIR__, 2) . '/var/tmp/customize-assets-*.json');
        if (! is_array($files)) {
            return;
        }

        foreach ($files as $file) {
            if (is_string($file) && is_file($file)) {
                unlink($file);
            }
        }
    }
}
