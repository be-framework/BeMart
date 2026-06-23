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
 * SQL-backed browser-form regression for the admin save-success banner.
 *
 * DEFECT (admin-save-success-flash): admin saves (会員編集・お届け先・
 * 各マスタ) 303-redirect back to the edit page via HtmlMutationResponse,
 * but the admin frame dropped EC-CUBE's flash include, so after saving the
 * operator saw the same form with NO 「保存しました」 signal.
 *
 * EC-CUBE ref: the admin write controllers call
 * `addSuccess('admin.common.save_complete', 'admin')` and the admin frame's
 * `@admin/alert.twig` renders it as an `alert alert-success` banner on the
 * POST-redirect-GET. This test proves the ported equivalent through the REAL
 * stack: a real admin customer-profile save (会員編集), then a GET of the
 * redirect Location, asserting the rendered admin page shows 「保存しました」.
 *
 * The fake/static suite cannot observe this — it has no session, so the
 * session-backed flash never crosses the redirect. This HTTP sibling does:
 * real php -S, real eccubedb_test, real session, real cookie jar.
 */
final class HttpSqlAdminSaveFlashFormTest extends TestCase
{
    private const HOST = '127.0.0.1:18221';
    private const ADMIN_ID = '1';
    private const CSRF_TOKEN = 'admin-save-flash-csrf-token';

    /** Seeded customer in eccubedb_test (sql/seed-dev.sh). */
    private const CUSTOMER_ID = '1';

    private static PhpServer|null $server = null;
    private string $cookieJar;

    public static function setUpBeforeClass(): void
    {
        self::$server = new PhpServer(self::HOST, __DIR__ . '/html-sql-index.php');
        self::$server->start();
    }

    public static function tearDownAfterClass(): void
    {
        self::$server?->stop();
        self::$server = null;
    }

    protected function setUp(): void
    {
        $this->cookieJar = (string) tempnam(sys_get_temp_dir(), 'bemart-sql-admin-flash-cookie-');
    }

    public function testAdminCustomerSaveShowsSaveCompleteBannerOnRedirectTarget(): void
    {
        // 1. GET the admin customer-edit form (real admin session via header).
        $edit = $this->request('GET', '/admin/customer?customerId=' . self::CUSTOMER_ID);
        $this->assertSame(200, $edit['status'], $edit['body']);
        $this->assertStringContainsString('id="customer_form"', $edit['body']);
        // The unsaved form must NOT already show the banner.
        $this->assertStringNotContainsString('保存しました', $edit['body']);
        $csrfToken = $this->csrfToken($edit['body']);

        // 2. POST the REAL browser field set (the submit button carries no
        //    name, so no `mode` field is sent — Customer.onPost always 303s
        //    through HtmlMutationResponse). Required: customerId/email/
        //    name01/name02; the rest are the persisted profile.
        $name01 = '保存';
        $name02 = 'テスト';
        $saved = $this->form('POST', '/admin/customer?customerId=' . self::CUSTOMER_ID, [
            'customerId' => self::CUSTOMER_ID,
            'email' => 'alice@example.com',
            'name01' => $name01,
            'name02' => $name02,
            'kana01' => 'ホゾン',
            'kana02' => 'テスト',
            'companyName' => '',
            'phoneNumber' => '0312345678',
            'postalCode' => '1500001',
            'pref' => '13',
            'addr01' => '渋谷区',
            'addr02' => '神宮前1-1-1',
            'birth' => '1990-04-01',
            'sex' => '2',
            'job' => '7',
            'csrfToken' => $csrfToken,
        ]);

        // 3. The save is a POST-redirect-GET back to the edit page.
        $this->assertSame(303, $saved['status'], $saved['body']);
        $location = $saved['headers']['Location'] ?? null;
        $this->assertIsString($location);
        $this->assertStringContainsString('/admin/customer?customerId=', $location);

        // 4. GET the redirect target — the rendered admin frame MUST now
        //    show EC-CUBE's 「保存しました」 success banner.
        $afterSave = $this->request('GET', $location);
        $this->assertSame(200, $afterSave['status'], $afterSave['body']);
        $this->assertStringContainsString('保存しました', $afterSave['body']);
        $this->assertStringContainsString('alert alert-success', $afterSave['body']);
        // The edited value persisted (proves we GET the real saved row).
        $this->assertStringContainsString('value="' . $name01 . '"', $afterSave['body']);
        $this->assertStringNotContainsString('Service Unavailable', $afterSave['body']);
        $this->assertStringNotContainsString('Fatal error', $afterSave['body']);

        // 5. Consume-once: a second GET (no save) must NOT show the banner.
        $reload = $this->request('GET', $location);
        $this->assertSame(200, $reload['status'], $reload['body']);
        $this->assertStringNotContainsString('保存しました', $reload['body']);
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
