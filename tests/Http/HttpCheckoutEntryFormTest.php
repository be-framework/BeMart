<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Http;

use Koriym\PhpServer\PhpServer;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use PHPUnit\Framework\TestCase;

use function escapeshellarg;
use function http_build_query;
use function is_string;
use function preg_match;
use function shell_exec;
use function sprintf;
use function str_contains;
use function sys_get_temp_dir;
use function tempnam;

/**
 * Browser-form smoke for the anonymous cart → checkout entry feature.
 *
 * Hypermedia workflow tests call Resources with perfect parameters; this
 * test submits application/x-www-form-urlencoded forms through the real HTTP
 * router so product/cart form fields, cookies and redirects are covered too.
 */
final class HttpCheckoutEntryFormTest extends TestCase
{
    private const HOST = '127.0.0.1:18181';

    private static PhpServer|null $server = null;
    private string $cookieJar;

    public static function setUpBeforeClass(): void
    {
        self::$server = new PhpServer(self::HOST, __DIR__ . '/index.php');
        self::$server->start();
    }

    protected function setUp(): void
    {
        $this->cookieJar = (string) tempnam(sys_get_temp_dir(), 'bemart-browser-form-cookie-');
    }

    public function testAnonymousCartFormsAndCheckoutEntryDoNotExposeJsonErrors(): void
    {
        $added = $this->form('POST', '/cart/item', [
            'productCode' => 'sample-001',
            'quantity' => '1',
            'operation' => 'add',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(303, $added['status']);
        $this->assertSame('/cart', $added['headers']['Location'] ?? null);

        $cart = $this->form('GET', '/cart');
        $this->assertSame(200, $cart['status']);
        $this->assertStringContainsString('ec-cartRow__amountUpForm', $cart['body']);
        $this->assertStringContainsString('rel="goCheckoutEntry"', $cart['body']);
        $this->assertStringNotContainsString('"code":404', $cart['body']);
        $this->assertStringNotContainsString('"code":401', $cart['body']);

        $increased = $this->form('POST', '/cart/item', [
            'productCode' => 'sample-001',
            'operation' => 'up',
            'quantity' => '4',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(303, $increased['status']);
        $this->assertSame('/cart', $increased['headers']['Location'] ?? null);
        $this->assertStringNotContainsString('"code":404', $increased['body']);

        $decreased = $this->form('POST', '/cart/item', [
            'productCode' => 'sample-001',
            'operation' => 'down',
            'quantity' => '3',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(303, $decreased['status']);
        $this->assertSame('/cart', $decreased['headers']['Location'] ?? null);

        $removed = $this->form('POST', '/cart/item', [
            'productCode' => 'sample-001',
            'operation' => 'remove',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(303, $removed['status']);
        $this->assertSame('/cart', $removed['headers']['Location'] ?? null);

        $entry = $this->form('GET', '/shopping');
        $this->assertSame(303, $entry['status']);
        $this->assertSame('/shopping/login', $entry['headers']['Location'] ?? null);
        $this->assertStringNotContainsString('"code":401', $entry['body']);
        $this->assertStringNotContainsString('doConfirmOrder', $entry['body']);
    }

    public function testNonMemberEmptyBrowserFormRendersValidationErrors(): void
    {
        $form = $this->form('GET', '/shopping/non-member');
        $this->assertSame(200, $form['status']);
        $this->assertStringContainsString('<h1>お客様情報の入力</h1>', $form['body']);
        $this->assertStringContainsString('name="pref"', $form['body']);
        $this->assertStringContainsString('<option value="13">東京都</option>', $form['body']);

        $rejected = $this->form('POST', '/shopping/non-member', [
            'name01' => '',
            'name02' => '',
            'kana01' => '',
            'kana02' => '',
            'companyName' => '',
            'email' => '',
            'email_confirm' => '',
            'phoneNumber' => '',
            'postalCode' => '',
            'pref' => '',
            'addr01' => '',
            'addr02' => '',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(400, $rejected['status']);
        $this->assertStringContainsString('text/html', $rejected['headers']['Content-Type'] ?? '');
        $this->assertStringContainsString('<h1>お客様情報の入力</h1>', $rejected['body']);
        $this->assertStringContainsString('入力してください。', $rejected['body']);
        $this->assertStringNotContainsString('Invalid parameter type', $rejected['body']);
        $this->assertStringNotContainsString('application/json', $rejected['headers']['Content-Type'] ?? '');
    }

    /**
     * @param array<string, string> $fields
     * @return array{status: int, headers: array<string, string>, body: string}
     */
    private function form(string $method, string $path, array $fields = []): array
    {
        $jar = escapeshellarg($this->cookieJar);
        $curl = sprintf('curl -s -i -b %s -c %s', $jar, $jar);
        if ($method !== 'GET') {
            $curl .= ' -X ' . escapeshellarg($method);
            $curl .= ' -d ' . escapeshellarg(http_build_query($fields));
        }

        $curl .= ' ' . escapeshellarg('http://' . self::HOST . $path);
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
