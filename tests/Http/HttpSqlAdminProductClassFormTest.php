<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Http;

use Koriym\PhpServer\PhpServer;
use PDO;
use PHPUnit\Framework\TestCase;

use function escapeshellarg;
use function explode;
use function http_build_query;
use function is_string;
use function parse_str;
use function parse_url;
use function preg_match;
use function preg_match_all;
use function preg_split;
use function shell_exec;
use function sprintf;
use function str_contains;
use function sys_get_temp_dir;
use function tempnam;
use function trim;

/**
 * SQL-backed REAL-browser regression for the admin 商品規格 editor.
 *
 * Pins the actual browser submission of `/admin/product/product-class`
 * (EC-CUBE `doRegisterProductClass`). The 新規規格 matrix row posts the
 * AdminProductClassForm leaf field names — `product_code`, `price02`,
 * `stock`, `stock_unlimited` (scalar checkbox), `delivery_fee` — plus the
 * hidden camelCase parent `productCode`. Three regressions are guarded:
 *
 *  1. EMPTY-INT 400: a blank money/stock field posts `""`; the resource
 *     must coerce it to int instead of 400-ing on the non-nullable int
 *     boundary.
 *  2. FIELD-NAME MISMATCH: the resource must bind the snake_case field
 *     names the form ACTUALLY posts (not camelCase), and the request param
 *     schema (`additionalProperties:false`) must declare every posted key.
 *  3. SQL TRUNCATION (1292): `tproduct_class_put.sql` must resolve
 *     product_id from the parent `productCode` STRING via its existing
 *     class row, not `CAST(productCode AS UNSIGNED)` against dtb_product.
 *
 * The proof is a NEW dtb_product_class row written for the parent product
 * (before/after row-count delta), with a Post/Redirect/Get response that
 * is not a 400/500.
 */
final class HttpSqlAdminProductClassFormTest extends TestCase
{
    private const HOST = '127.0.0.1:18193';
    private const ADMIN_ID = '1';
    private const CSRF_TOKEN = 'admin-product-class-form-csrf-token';
    private const PARENT_PRODUCT_CODE = 'admin-active-001';

    private static PhpServer|null $server = null;
    private string $cookieJar;

    public static function setUpBeforeClass(): void
    {
        self::$server = new PhpServer(self::HOST, __DIR__ . '/html-sql-index.php');
        self::$server->start();
    }

    protected function setUp(): void
    {
        $this->cookieJar = (string) tempnam(sys_get_temp_dir(), 'bemart-sql-admin-product-class-cookie-');
    }

    public function testRegisterProductClassFromRealBrowserWritesNewRow(): void
    {
        $pdo = $this->pdo();

        // GET the editor and harvest the REAL rendered new-class row fields.
        $edit = $this->request('GET', '/admin/product/product-class?productCode=' . self::PARENT_PRODUCT_CODE);
        $this->assertSame(200, $edit['status'], $edit['body']);
        $this->assertStringContainsString('id="product_class_new_row"', $edit['body']);
        // The 登録 submit must be present (the end-to-end affordance).
        $this->assertStringContainsString('登録</button>', $edit['body']);

        // Confirm the editable leaf inputs the browser will post.
        $fields = $this->harvestPostFields($edit['body']);
        $this->assertArrayHasKey('productCode', $fields);
        $this->assertArrayHasKey('product_code', $fields);
        $this->assertArrayHasKey('price02', $fields);
        $this->assertArrayHasKey('stock', $fields);
        $this->assertArrayHasKey('delivery_fee', $fields);

        // product_id this SKU must be attached to (resolved from the parent code).
        $expectedProductId = (int) $pdo
            ->query("SELECT product_id FROM dtb_product_class WHERE product_code = 'admin-active-001' LIMIT 1")
            ->fetchColumn();
        $this->assertGreaterThan(0, $expectedProductId);

        $before = $this->rowCount($pdo);

        // EXACT browser submission of the new-class row.
        $post = $this->form('POST', '/admin/product/product-class', [
            'csrfToken' => $this->csrfToken($edit['body']),
            'productCode' => self::PARENT_PRODUCT_CODE,
            'product_code' => 'admin-active-001-http',
            'price02' => '1480',
            'stock' => '7',
            'stock_unlimited' => '1',
            'delivery_fee' => '',
        ]);

        // NOT a transport/runtime failure, and a Post/Redirect/Get.
        $this->assertNotSame(400, $post['status'], $post['body']);
        $this->assertNotSame(500, $post['status'], $post['body']);
        $this->assertSame(303, $post['status'], $post['body']);
        $this->assertStringContainsString(
            '/admin/product/product-class?productCode=admin-active-001',
            $post['headers']['Location'] ?? '',
        );

        // A NEW dtb_product_class row actually exists.
        $after = $this->rowCount($pdo);
        $this->assertSame($before + 1, $after, sprintf('expected one new row (before=%d after=%d)', $before, $after));

        // The new row carries the resolved product_id and stock_unlimited semantics.
        $newRow = $pdo
            ->query('SELECT product_id, stock, stock_unlimited, price02, delivery_fee FROM dtb_product_class ORDER BY id DESC LIMIT 1')
            ->fetch(PDO::FETCH_ASSOC);
        $this->assertSame($expectedProductId, (int) $newRow['product_id']);
        $this->assertSame(1, (int) $newRow['stock_unlimited']);
        $this->assertNull($newRow['stock']); // unlimited → NULL stock
        $this->assertSame(1480, (int) $newRow['price02']);
        $this->assertSame(0, (int) $newRow['delivery_fee']); // blank → 0
    }

    private function rowCount(PDO $pdo): int
    {
        return (int) $pdo
            ->query("SELECT COUNT(*) FROM dtb_product_class WHERE product_code IN ('admin-active-001', 'admin-active-001-http')")
            ->fetchColumn();
    }

    /**
     * Harvest <input>/<button> name attributes inside the posted form.
     *
     * @return array<string, true>
     */
    private function harvestPostFields(string $html): array
    {
        $fields = [];
        if (preg_match_all('/name="([^"]+)"/', $html, $matches)) {
            foreach ($matches[1] as $name) {
                $fields[$name] = true;
            }
        }

        return $fields;
    }

    private function pdo(): PDO
    {
        $databaseUrl = $_SERVER['DATABASE_URL'] ?? $_ENV['DATABASE_URL'] ?? null;
        if (! is_string($databaseUrl) || $databaseUrl === '') {
            self::markTestSkipped('DATABASE_URL is not set; SQL product-class regression requires the eccubedb_test DB.');
        }

        $parts = parse_url($databaseUrl);
        $this->assertIsArray($parts);
        $query = [];
        if (isset($parts['query']) && is_string($parts['query'])) {
            parse_str($parts['query'], $query);
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $parts['host'] ?? '127.0.0.1',
            (int) ($parts['port'] ?? 3306),
            trim((string) ($parts['path'] ?? ''), '/'),
            is_string($query['charset'] ?? null) ? $query['charset'] : 'utf8mb4',
        );

        return new PDO(
            $dsn,
            $parts['user'] ?? 'root',
            $parts['pass'] ?? '',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
        );
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
