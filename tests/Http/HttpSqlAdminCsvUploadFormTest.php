<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Http;

use Koriym\PhpServer\PhpServer;
use PHPUnit\Framework\TestCase;

use function bin2hex;
use function dirname;
use function escapeshellarg;
use function explode;
use function file_put_contents;
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
use function unlink;

/**
 * SQL-backed browser-form regression for admin CSV uploads.
 *
 * The hypermedia CSV workflow posts a `csv` string directly to the action.
 * The admin browser UI posts `import_file` as multipart/form-data, so this
 * test pins the real HTTP boundary and readback after import.
 */
final class HttpSqlAdminCsvUploadFormTest extends TestCase
{
    private const HOST = '127.0.0.1:18186';
    private const ADMIN_ID = 'ad000000000000000000000000000001';
    private const CSRF_TOKEN = 'admin-csv-upload-csrf-token';

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
        $this->cookieJar = (string) tempnam(sys_get_temp_dir(), 'bemart-sql-admin-csv-cookie-');
    }

    public function testProductCsvMultipartUploadCreatesProductAndRedirectsToList(): void
    {
        $form = $this->request('GET', '/admin/product/csv-product');
        $this->assertSame(200, $form['status'], $form['body']);
        $this->assertStringContainsString('enctype="multipart/form-data"', $form['body']);
        $this->assertStringContainsString('name="import_file"', $form['body']);

        $productCode = 'csv-upload-' . bin2hex(random_bytes(4));
        $csv = "productCode,productName,price02,stock,productStatus,description,searchWord,note\n"
            . "{$productCode},CSV Upload Product,1234,7,1,Created through multipart upload,csv upload,HTTP regression\n";
        $uploaded = $this->multipart('/admin/product-csv', 'products.csv', $csv);

        $this->assertSame(303, $uploaded['status'], $uploaded['body']);
        $this->assertSame('/admin/product-list', $uploaded['headers']['Location'] ?? null);

        $product = $this->request('GET', '/admin/product?productCode=' . $productCode);
        $this->assertSame(200, $product['status'], $product['body']);
        $this->assertStringContainsString($productCode, $product['body']);
        $this->assertStringContainsString('CSV Upload Product', $product['body']);
    }

    public function testCategoryCsvMultipartUploadCreatesCategoryAndRedirectsToList(): void
    {
        $form = $this->request('GET', '/admin/product/csv-category');
        $this->assertSame(200, $form['status'], $form['body']);
        $this->assertStringContainsString('enctype="multipart/form-data"', $form['body']);
        $this->assertStringContainsString('name="import_file"', $form['body']);

        $categoryName = 'CSV Upload Category ' . bin2hex(random_bytes(4));
        $csv = "category_id,category_name,parent_category_id\n,{$categoryName},\n";
        $uploaded = $this->multipart('/admin/category/csv?csv=', 'categories.csv', $csv);

        $this->assertSame(303, $uploaded['status'], $uploaded['body']);
        $this->assertSame('/admin/category/category-list', $uploaded['headers']['Location'] ?? null);

        $list = $this->request('GET', '/admin/category/category-list');
        $this->assertSame(200, $list['status'], $list['body']);
        $this->assertStringContainsString($categoryName, $list['body']);
    }

    public function testClassNameCsvMultipartUploadCreatesClassNameAndRedirectsToList(): void
    {
        $form = $this->request('GET', '/admin/product/csv-class-name');
        $this->assertSame(200, $form['status'], $form['body']);
        $this->assertStringContainsString('enctype="multipart/form-data"', $form['body']);
        $this->assertStringContainsString('name="import_file"', $form['body']);

        $className = 'CSV Class ' . bin2hex(random_bytes(4));
        $csv = "class_name_id,class_name,backend_name\n,{$className},{$className}\n";
        $uploaded = $this->multipart('/admin/product/csv-class-name?csv=', 'class_names.csv', $csv);

        $this->assertSame(303, $uploaded['status'], $uploaded['body']);
        $this->assertSame('/admin/class-name/class-name-list', $uploaded['headers']['Location'] ?? null);

        $list = $this->request('GET', '/admin/class-name/class-name-list');
        $this->assertSame(200, $list['status'], $list['body']);
        $this->assertStringContainsString($className, $list['body']);
    }

    public function testClassCategoryCsvMultipartUploadCreatesClassCategoryAndRedirectsToList(): void
    {
        $className = 'CSV Parent ' . bin2hex(random_bytes(4));
        $classNameId = $this->createClassNameViaForm($className);

        $form = $this->request('GET', '/admin/product/csv-class-category');
        $this->assertSame(200, $form['status'], $form['body']);
        $this->assertStringContainsString('enctype="multipart/form-data"', $form['body']);
        $this->assertStringContainsString('name="import_file"', $form['body']);

        $classCategory = 'CSV CC ' . bin2hex(random_bytes(4));
        $csv = "class_category_id,class_name_id,class_category_name,backend_name\n,{$classNameId},{$classCategory},{$classCategory}\n";
        $uploaded = $this->multipart('/admin/product/csv-class-category?csv=', 'class_categories.csv', $csv);

        $this->assertSame(303, $uploaded['status'], $uploaded['body']);
        $this->assertSame('/admin/class-category/class-category-list', $uploaded['headers']['Location'] ?? null);

        $list = $this->request('GET', '/admin/class-category/class-category-list?classNameId=' . $classNameId);
        $this->assertSame(200, $list['status'], $list['body']);
        $this->assertStringContainsString($classCategory, $list['body']);
    }

    public function testCsvConfigFormUpdatesOrderCsvExportLayout(): void
    {
        $form = $this->request('GET', '/admin/csv-config?csvType=3');
        $this->assertSame(200, $form['status'], $form['body']);
        $this->assertStringContainsString('action="/admin/csv-config"', $form['body']);
        $this->assertStringContainsString('name="csrfToken"', $form['body']);
        $this->assertStringContainsString('value="' . self::CSRF_TOKEN . '"', $form['body']);

        $updated = $this->form('POST', '/admin/csv-config', [
            'csvType' => '3',
            'csvOutput' => ['paymentTotal', 'orderNo'],
            'csvNotOutput' => ['orderDate', 'customerName'],
            'csrfToken' => $this->csrfToken($form['body']),
        ]);

        $this->assertSame(303, $updated['status'], $updated['body']);
        $this->assertSame('/admin/csv-config?csvType=3', $updated['headers']['Location'] ?? null);

        $csv = $this->request('GET', '/admin/order/export-order');
        $this->assertSame(200, $csv['status'], $csv['body']);
        $this->assertStringStartsWith("paymentTotal,orderNo\n", $csv['body']);
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
    private function multipart(string $path, string $fileName, string $csv): array
    {
        $file = (string) tempnam(sys_get_temp_dir(), 'bemart-csv-upload-');
        file_put_contents($file, $csv);

        $jar = escapeshellarg($this->cookieJar);
        $curl = sprintf(
            'curl -s -i -b %s -c %s -H %s -H %s -F %s -F %s %s',
            $jar,
            $jar,
            escapeshellarg('X-BeMart-Test-Admin-Id: ' . self::ADMIN_ID),
            escapeshellarg('X-BeMart-Test-Csrf-Token: ' . self::CSRF_TOKEN),
            escapeshellarg('csrfToken=' . self::CSRF_TOKEN),
            escapeshellarg('import_file=@' . $file . ';type=text/csv;filename=' . $fileName),
            escapeshellarg('http://' . self::HOST . $path),
        );
        $raw = shell_exec($curl);
        unlink($file);
        $this->assertIsString($raw);

        return $this->parseResponse($raw);
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

    private function createClassNameViaForm(string $className): string
    {
        $jar = escapeshellarg($this->cookieJar);
        $curl = sprintf(
            'curl -s -i -b %s -c %s -H %s -H %s -F %s -F %s %s',
            $jar,
            $jar,
            escapeshellarg('X-BeMart-Test-Admin-Id: ' . self::ADMIN_ID),
            escapeshellarg('X-BeMart-Test-Csrf-Token: ' . self::CSRF_TOKEN),
            escapeshellarg('csrfToken=' . self::CSRF_TOKEN),
            escapeshellarg('classNameLabel=' . $className),
            escapeshellarg('http://' . self::HOST . '/admin/class-name/class-name-list'),
        );
        $raw = shell_exec($curl);
        $this->assertIsString($raw);

        $created = $this->parseResponse($raw);
        $this->assertSame(303, $created['status'], $created['body']);

        $list = $this->request('GET', '/admin/class-name/class-name-list');
        $this->assertSame(200, $list['status'], $list['body']);
        $this->assertSame(
            1,
            preg_match('/<tr[^>]*data-class-name-id="(\d+)"[^>]*>.*?' . preg_quote($className, '/') . '/s', $list['body'], $match),
            $list['body'],
        );

        return $match[1];
    }

    private function csrfToken(string $body): string
    {
        $this->assertSame(1, preg_match('/name="csrfToken" value="([^"]*)"/', $body, $match), $body);

        return $match[1];
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
