<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Http;

use Koriym\PhpServer\PhpServer;
use PHPUnit\Framework\TestCase;

use function bin2hex;
use function dirname;
use function escapeshellarg;
use function explode;
use function html_entity_decode;
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

use const ENT_HTML5;
use const ENT_QUOTES;

/**
 * SQL-backed browser-form regression for admin manual order creation.
 *
 * Business prerequisites are created through HTTP operations. The order itself
 * must be created from the admin order-list -> blank order editor form, not by
 * direct test-only POST to /admin/order/create.
 */
final class HttpSqlAdminOrderCreateFormTest extends TestCase
{
    private const HOST = '127.0.0.1:18198';
    private const ADMIN_ID = '1';
    private const CSRF_TOKEN = 'admin-order-create-form-csrf-token';

    private static PhpServer|null $server = null;
    private string $cookieJar;

    public static function setUpBeforeClass(): void
    {
        self::clearCompiledContextCache();
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
        $this->cookieJar = (string) tempnam(sys_get_temp_dir(), 'bemart-sql-admin-order-create-cookie-');
    }

    public function testAdminCanCreateManualOrderThroughBlankEditorForm(): void
    {
        $suffix = bin2hex(random_bytes(4));
        $customerId = $this->createCustomer($suffix);
        $productCode = $this->createPublishedProduct($suffix);
        $productName = 'Manual Order Product ' . $suffix;

        $list = $this->request('GET', '/admin/order-list');
        $this->assertSame(200, $list['status'], $list['body']);
        $this->assertStringContainsString('rel="doCreateOrder"', $list['body']);

        $editor = $this->request('GET', '/admin/order/edit');
        $this->assertSame(200, $editor['status'], $editor['body']);
        $this->assertStringContainsString('受注登録', $editor['body']);
        $this->assertStringContainsString('action="/admin/order/create"', $editor['body']);
        $this->assertStringContainsString('name="orderItems[0][productCode]"', $editor['body']);

        $created = $this->form('POST', '/admin/order/create', [
            'customerId' => $customerId,
            'paymentMethodId' => '1',
            'orderItems' => [[
                'productCode' => $productCode,
                'productName' => $productName,
                'unitPrice' => '1200',
                'quantity' => '2',
            ]],
            'deliveryFeeTotal' => '0',
            'charge' => '0',
            'discount' => '0',
            'csrfToken' => $this->csrfToken($editor['body']),
        ]);

        $this->assertSame(303, $created['status'], $created['body']);
        $location = $created['headers']['Location'] ?? '';
        $this->assertStringStartsWith('/admin/order?orderNo=', $location);

        $detail = $this->request('GET', $location);
        $this->assertSame(200, $detail['status'], $detail['body']);
        $this->assertStringContainsString($customerId, $detail['body']);
        $this->assertStringContainsString($productCode, $detail['body']);
        $this->assertStringContainsString($productName, $detail['body']);
        $this->assertStringContainsString('￥2,640', $detail['body']);
    }

    public function testManualOrderCreateFormRejectsInvalidNumericFieldAsBadRequest(): void
    {
        $editor = $this->request('GET', '/admin/order/edit');
        $this->assertSame(200, $editor['status'], $editor['body']);

        $response = $this->form('POST', '/admin/order/create', [
            'customerId' => '1',
            'paymentMethodId' => 'invalid-payment',
            'orderItems' => [[
                'productCode' => 'sample-001',
                'productName' => 'サンプル商品',
                'unitPrice' => '1200',
                'quantity' => '1',
            ]],
            'deliveryFeeTotal' => '0',
            'charge' => '0',
            'discount' => '0',
            'csrfToken' => $this->csrfToken($editor['body']),
        ]);

        $this->assertSame(400, $response['status'], $response['body']);
        $this->assertStringContainsString('paymentMethodId', $response['body']);
    }

    private function createCustomer(string $suffix): string
    {
        $email = 'manual-order-' . $suffix . '@example.test';
        $created = $this->form('POST', '/admin/create-customer', [
            'email' => $email,
            'password' => 'manual-order-password-2026',
            'name01' => '受注',
            'name02' => '作成',
            'kana01' => 'ジュチュウ',
            'kana02' => 'サクセイ',
            'phoneNumber' => '0312345678',
            'postalCode' => '1000001',
            'pref' => '13',
            'addr01' => '千代田区',
            'addr02' => '管理1-1',
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(201, $created['status'], $created['body']);
        $location = $created['headers']['Location'] ?? '';
        $this->assertStringStartsWith('/admin/customer?email=', $location);

        $detail = $this->request('GET', $location);
        $this->assertSame(200, $detail['status'], $detail['body']);
        $this->assertStringContainsString($email, $detail['body']);

        $list = $this->request('GET', '/admin/customer-list?emailKeyword=' . $email);
        $this->assertSame(200, $list['status'], $list['body']);
        $customerId = $this->customerIdFromList($list['body'], $email);
        $this->assertNotSame('', $customerId, $created['body']);

        return $customerId;
    }

    private function createPublishedProduct(string $suffix): string
    {
        $productCode = 'manual-order-' . $suffix;
        $created = $this->form('POST', '/admin/product', [
            'productCode' => $productCode,
            'productName' => 'Manual Order Product ' . $suffix,
            'price02' => '1200',
            'stock' => '5',
            'productStatus' => '1',
            'description' => 'Created through admin HTTP before manual order.',
            'searchWord' => 'manual order create',
            'note' => 'HTTP regression setup through web operation.',
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(201, $created['status'], $created['body']);
        $this->assertStringContainsString('productCode=' . $productCode, $created['headers']['Location'] ?? '');

        return $productCode;
    }

    /**
     * @param array<string, mixed> $fields
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

        return html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5);
    }

    private function customerIdFromList(string $html, string $email): string
    {
        $pattern = '#<tr id="ex-customer-([^"]+)">.*?' . preg_quote($email, '#') . '#s';
        $this->assertSame(1, preg_match($pattern, $html, $match), $html);
        $customerId = html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5);
        $this->assertNotSame('', $customerId);

        return $customerId;
    }
}
