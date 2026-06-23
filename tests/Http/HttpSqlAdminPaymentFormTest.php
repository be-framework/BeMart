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
 * SQL-backed browser-form regression for the admin payment-method editor.
 *
 * Pins the REAL browser submission of `/admin/payment/payment`. EC-CUBE's
 * visible toggle is a single on/off checkbox and the rule-min/rule-max money
 * fields are optional; a browser therefore posts `visible=1` (scalar, or
 * nothing when unchecked) and `ruleMax=` (empty string when blank).
 *
 * Two regressions are guarded here:
 *  1. The `visible` checkbox must render as a SCALAR input (`name="visible"`),
 *     not an Aura array checkbox (`name="visible[]"`). The array form is
 *     rejected by the `?bool $visible` resource boundary with a 400, so the
 *     payment could never be saved from a real browser.
 *  2. Blank optional money fields arrive as empty strings; the resource must
 *     coerce `''` to null instead of 400-ing on the `?int` boundary.
 *
 * The corrected observable: a 303 Post/Redirect/Get to the editor with the
 * EC-CUBE「保存しました」success banner on the redirected GET.
 */
final class HttpSqlAdminPaymentFormTest extends TestCase
{
    private const HOST = '127.0.0.1:18189';
    private const ADMIN_ID = '1';
    private const CSRF_TOKEN = 'admin-payment-form-csrf-token';

    private static PhpServer|null $server = null;
    private string $cookieJar;

    public static function setUpBeforeClass(): void
    {
        self::$server = new PhpServer(self::HOST, __DIR__ . '/html-sql-index.php');
        self::$server->start();
    }

    protected function setUp(): void
    {
        $this->cookieJar = (string) tempnam(sys_get_temp_dir(), 'bemart-sql-admin-payment-cookie-');
    }

    public function testPaymentVisibleToggleRendersScalarCheckbox(): void
    {
        $edit = $this->request('GET', '/admin/payment/payment?paymentId=1');
        $this->assertSame(200, $edit['status'], $edit['body']);

        // The toggle must be a single scalar checkbox a browser posts as
        // `visible=1`; the Aura array form `name="visible[]"` would 400.
        $this->assertStringContainsString('name="visible" value="1"', $edit['body']);
        $this->assertStringNotContainsString('name="visible[]"', $edit['body']);
    }

    public function testPaymentUpdateWithBrowserFieldsRedirectsAndShowsSaveBanner(): void
    {
        $edit = $this->request('GET', '/admin/payment/payment?paymentId=1');
        $this->assertSame(200, $edit['status'], $edit['body']);

        // EXACT browser submission: scalar `visible=1`, blank `ruleMax`.
        $updated = $this->form('POST', '/admin/payment/payment?paymentId=1&_method=put', [
            'csrfToken' => $this->csrfToken($edit['body']),
            'paymentId' => '1',
            '_method' => 'put',
            'paymentMethodName' => 'HTTP更新支払',
            'charge' => '300',
            'ruleMin' => '0',
            'ruleMax' => '',
            'visible' => '1',
        ]);

        $this->assertSame(303, $updated['status'], $updated['body']);
        $this->assertSame('/admin/payment/payment?paymentId=1', $updated['headers']['Location'] ?? null);

        $after = $this->request('GET', '/admin/payment/payment?paymentId=1');
        $this->assertSame(200, $after['status'], $after['body']);
        $this->assertStringContainsString('保存しました', $after['body']);
        $this->assertStringContainsString('HTTP更新支払', $after['body']);
    }

    public function testPaymentUpdateWithUncheckedVisibleStillSaves(): void
    {
        $edit = $this->request('GET', '/admin/payment/payment?paymentId=1');
        $this->assertSame(200, $edit['status'], $edit['body']);

        // Unchecked checkbox: a browser omits `visible` entirely.
        $updated = $this->form('POST', '/admin/payment/payment?paymentId=1&_method=put', [
            'csrfToken' => $this->csrfToken($edit['body']),
            'paymentId' => '1',
            '_method' => 'put',
            'paymentMethodName' => 'HTTP無効支払',
            'charge' => '0',
            'ruleMin' => '',
            'ruleMax' => '',
        ]);

        $this->assertSame(303, $updated['status'], $updated['body']);
        $this->assertSame('/admin/payment/payment?paymentId=1', $updated['headers']['Location'] ?? null);
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
