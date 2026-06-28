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
use function in_array;
use function is_dir;
use function is_string;
use function parse_str;
use function parse_url;
use function preg_match;
use function preg_split;
use function random_bytes;
use function shell_exec;
use function sprintf;
use function str_contains;
use function sys_get_temp_dir;
use function tempnam;
use function trim;

use const PHP_URL_QUERY;

/**
 * SQL-backed browser-form regression for admin order bulk delete.
 *
 * The order under test is created through storefront HTTP checkout, then
 * cancelled through the real admin order-list bulk form (`ids[]`, CSRF, PRG).
 */
final class HttpSqlAdminOrderBulkDeleteFormTest extends TestCase
{
    private const HOST = '127.0.0.1:18197';
    private const ADMIN_ID = '1';
    private const CSRF_TOKEN = 'admin-order-bulk-delete-form-csrf-token';

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
        $this->cookieJar = (string) tempnam(sys_get_temp_dir(), 'bemart-sql-admin-order-bulk-cookie-');
    }

    public function testAdminCanBulkDeleteCheckoutOrderThroughOrderListForm(): void
    {
        $orderNo = $this->createCheckoutOrder();

        $list = $this->request('GET', '/admin/order-list');
        $this->assertSame(200, $list['status'], $list['body']);
        $this->assertStringContainsString('id="form_bulk"', $list['body']);
        $this->assertStringContainsString('name="mode" value="order_bulk_delete_form"', $list['body']);
        $this->assertStringContainsString('name="csrfToken" value="', $list['body']);
        $this->assertStringContainsString('attr(\'action\', "/admin/order/bulk-delete")', $list['body']);

        $deleted = $this->form('POST', '/admin/order/bulk-delete', [
            'ids' => [$orderNo],
            'mode' => 'order_bulk_delete_form',
            'csrfToken' => $this->csrfToken($list['body']),
        ]);

        $this->assertSame(303, $deleted['status'], $deleted['body']);
        $this->assertSame('/admin/order-list', $deleted['headers']['Location'] ?? null);

        $detail = $this->request('GET', '/admin/order?orderNo=' . $orderNo);
        $this->assertSame(200, $detail['status'], $detail['body']);
        $this->assertStringContainsString($orderNo, $detail['body']);
        $this->assertStringContainsString('注文取消', $detail['body']);
    }

    private function createCheckoutOrder(): string
    {
        $productCode = $this->createPublishedProduct();

        $added = $this->form('POST', '/cart/item', [
            'productCode' => $productCode,
            'quantity' => '1',
            'operation' => 'add',
            'csrfToken' => self::CSRF_TOKEN,
        ]);
        $this->assertSame(303, $added['status'], $added['body']);

        $form = $this->request('GET', '/shopping/non-member');
        $this->assertSame(200, $form['status'], $form['body']);

        $email = 'bulk-delete-order-' . bin2hex(random_bytes(4)) . '@example.test';
        $submitted = $this->form('POST', '/shopping/non-member', [
            'name01' => '山田',
            'name02' => '太郎',
            'kana01' => 'ヤマダ',
            'kana02' => 'タロウ',
            'companyName' => '',
            'email' => $email,
            'email_confirm' => $email,
            'phoneNumber' => '0312345678',
            'postalCode' => '1000001',
            'pref' => '13',
            'addr01' => '千代田区',
            'addr02' => '1-1',
            'csrfToken' => $this->csrfToken($form['body']),
        ]);
        $this->assertSame(303, $submitted['status'], $submitted['body']);
        $confirmLocation = $submitted['headers']['Location'] ?? '';
        $this->assertStringStartsWith('/shopping/confirm?preOrderId=', $confirmLocation);

        $confirm = $this->request('GET', $confirmLocation);
        $this->assertSame(200, $confirm['status'], $confirm['body']);
        $this->assertStringContainsString($email, $confirm['body']);
        $this->assertStringContainsString('action="/shopping/checkout', $confirm['body']);

        $checkout = $this->form('POST', '/shopping/checkout', [
            'preOrderId' => $this->inputValue($confirm['body'], 'preOrderId'),
            'csrfToken' => $this->csrfToken($confirm['body']),
        ]);
        $this->assertTrue(
            in_array($checkout['status'], [201, 303], true),
            $checkout['body'],
        );
        $orderNo = $this->queryValue($checkout['headers']['Location'] ?? '', 'orderNo');
        $this->assertNotSame('', $orderNo, $checkout['body']);

        return $orderNo;
    }

    private function createPublishedProduct(): string
    {
        $suffix = bin2hex(random_bytes(4));
        $productCode = 'bulkdel-' . $suffix;
        $created = $this->form('POST', '/admin/product', [
            'productCode' => $productCode,
            'productName' => 'Bulk Delete Checkout Product ' . $suffix,
            'price02' => '1200',
            'stock' => '5',
            'productStatus' => '1',
            'description' => 'Created through admin HTTP before checkout.',
            'searchWord' => 'bulk delete checkout',
            'note' => 'HTTP regression setup through web operation.',
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(201, $created['status'], $created['body']);
        $this->assertStringContainsString('productCode=' . $productCode, $created['headers']['Location'] ?? '');

        return $productCode;
    }

    /**
     * @param array<string, string|list<string>> $fields
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

        return $match[1];
    }

    private function inputValue(string $body, string $name): string
    {
        $this->assertSame(1, preg_match('/name="' . $name . '" value="([^"]*)"/', $body, $match), $body);

        return $match[1];
    }

    private function queryValue(string $location, string $name): string
    {
        $query = parse_url($location, PHP_URL_QUERY);
        $this->assertIsString($query);
        $values = [];
        parse_str($query, $values);
        $value = $values[$name] ?? '';

        return is_string($value) ? $value : '';
    }
}
