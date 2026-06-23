<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Http;

use Koriym\PhpServer\PhpServer;
use PHPUnit\Framework\TestCase;

use function escapeshellarg;
use function explode;
use function is_string;
use function preg_match;
use function preg_split;
use function shell_exec;
use function sprintf;
use function str_contains;
use function trim;

/**
 * SQL-backed browser regression for the product-not-found 404 page.
 *
 * Defect (product-not-found-404): GET-ing the product detail screen with an
 * unknown product code returned a 404, but the user-visible message leaked an
 * internal `page://self/...` resource URI instead of a readable not-found copy
 * (EC-CUBE ref: ProductController::detail -> NotFoundHttpException -> the 404
 * error template). The fix renders EC-CUBE's faithful 404 message
 * (exception.error_title_not_found = 「ページがみつかりません。」) for the
 * BEAR ResourceNotFoundException, and keeps the canonical
 * `?productCode=` path emitting the domain ProductNotFoundException message.
 *
 * Proven through the real stack: a real php -S server (html-eccube-sql-hal-app
 * context + eccubedb_test DB + session), real curl, the rendered HTML error
 * page asserted for a readable signal and the ABSENCE of the internal URI /
 * any framework stack trace.
 */
final class HttpSqlProductNotFoundTest extends TestCase
{
    private const HOST = '127.0.0.1:18209';

    private static PhpServer|null $server = null;

    public static function setUpBeforeClass(): void
    {
        self::$server = new PhpServer(self::HOST, __DIR__ . '/html-sql-index.php');
        self::$server->start();
    }

    public function testPathStyleUnknownProductRendersReadable404WithoutLeakingResourceUri(): void
    {
        $response = $this->get('/product/does-not-exist-zzz');

        $this->assertSame(404, $response['status'], $response['body']);
        // User-visible readable 404 copy (EC-CUBE exception.error_title_not_found).
        $this->assertStringContainsString('ページがみつかりません。', $response['body']);
        $this->assertStringContainsString('<title>404 Not Found - BeMart</title>', $response['body']);
        // The internal resource URI must never reach the browser.
        $this->assertStringNotContainsString('page://self', $response['body']);
        // Not a framework error page / stack trace / 500.
        $this->assertStringNotContainsString('xdebug-error', $response['body']);
        $this->assertStringNotContainsString('Fatal error', $response['body']);
        $this->assertStringNotContainsString('Uncaught', $response['body']);
        $this->assertStringNotContainsString('Service Unavailable', $response['body']);
    }

    public function testCanonicalUnknownProductCodeRendersDomainNotFoundMessage(): void
    {
        $response = $this->get('/product?productCode=does-not-exist-zzz');

        $this->assertSame(404, $response['status'], $response['body']);
        // The Be domain ProductNotFoundException #[Message] (ja) — reached
        // because the canonical query-param path resolves to the resource.
        $this->assertStringContainsString('指定された商品コードに該当する商品が見つかりません。', $response['body']);
        $this->assertStringContainsString('<title>404 Not Found - BeMart</title>', $response['body']);
        $this->assertStringNotContainsString('page://self', $response['body']);
        $this->assertStringNotContainsString('xdebug-error', $response['body']);
        $this->assertStringNotContainsString('Fatal error', $response['body']);
        $this->assertStringNotContainsString('Uncaught', $response['body']);
    }

    public function testKnownProductCodeStillRendersDetailPage(): void
    {
        $response = $this->get('/product?productCode=admin-active-001');

        $this->assertSame(200, $response['status'], $response['body']);
        $this->assertStringNotContainsString('ページがみつかりません。', $response['body']);
        $this->assertStringNotContainsString('page://self', $response['body']);
    }

    /** @return array{status: int, headers: array<string, string>, body: string} */
    private function get(string $path): array
    {
        $curl = sprintf('curl -s -i %s', escapeshellarg('http://' . self::HOST . $path));
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
