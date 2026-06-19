<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Http;

use BEAR\Resource\Annotation\Link;
use Koriym\PhpServer\PhpServer;
use MyVendor\BeMart\Resource\Page\Admin\Product\Edit;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

use function bin2hex;
use function escapeshellarg;
use function explode;
use function html_entity_decode;
use function http_build_query;
use function is_string;
use function preg_match;
use function preg_split;
use function random_bytes;
use function shell_exec;
use function sprintf;
use function str_contains;
use function strlen;
use function strtolower;
use function substr;
use function sys_get_temp_dir;
use function tempnam;
use function trim;

/**
 * ALPS-affordance round-trip for the admin product editor.
 *
 * Resource-level tests POST straight to `page://self/admin/product` (PUT), so
 * they verify onPut but never the rendered HTML <form> a browser actually
 * submits. This test closes that leg: it locates the affordance by its ALPS id
 * (the `data-alps` microformat), asserts the rendered action+method match the
 * resource's own #[Link(rel: 'doUpdateProduct', …)] contract, and submits it AS
 * RENDERED. A form whose action/method drift from the contract (e.g. the 405 a
 * stale "登録" button produced) fails here instead of in the browser.
 */
final class AffordanceProductFormTest extends TestCase
{
    private const HOST = '127.0.0.1:18190';
    private const ADMIN_ID = '1';
    private const CSRF_TOKEN = 'affordance-product-form-csrf-token';

    private static PhpServer|null $server = null;
    private string $cookieJar;

    public static function setUpBeforeClass(): void
    {
        self::$server = new PhpServer(self::HOST, __DIR__ . '/html-sql-index.php');
        self::$server->start();
    }

    protected function setUp(): void
    {
        $this->cookieJar = (string) tempnam(sys_get_temp_dir(), 'bemart-affordance-product-cookie-');
    }

    public function testProductWriteAffordancesMatchContractAndSubmit(): void
    {
        $code = 'afford-' . bin2hex(random_bytes(4));

        // doCreateProduct — the blank editor's affordance, submitted as rendered.
        $newEditor = $this->request('GET', '/admin/product/edit');
        $this->assertSame(200, $newEditor['status'], $newEditor['body']);
        $created = $this->submitAffordance($newEditor, 'doCreateProduct', [
            'productCode' => $code,
            'productName' => 'affordance-new',
            'price02' => '1000',
        ]);
        $this->assertNotSame(405, $created['status'], 'doCreateProduct affordance 405ed');
        $this->assertContains($created['status'], [201, 303], $created['body']);

        // doUpdateProduct — the "登録" path that produced the 405. Editing the
        // product we just created, submitted exactly as the page renders it.
        $editor = $this->request('GET', '/admin/product/edit?productCode=' . $code);
        $this->assertSame(200, $editor['status'], $editor['body']);
        $updated = $this->submitAffordance($editor, 'doUpdateProduct', [
            'productCode' => $code,
            'productName' => 'affordance-updated',
            'price02' => '1234',
        ]);
        $this->assertNotSame(405, $updated['status'], 'doUpdateProduct affordance 405ed');
        $this->assertSame(303, $updated['status'], $updated['body']);
    }

    /**
     * Locate the affordance by ALPS id, assert its rendered action+method match
     * the resource's #[Link] contract, then submit it AS RENDERED.
     *
     * @param array{status: int, headers: array<string, string>, body: string} $page
     * @param array<string, string>                                            $fields
     *
     * @return array{status: int, headers: array<string, string>, body: string}
     */
    private function submitAffordance(array $page, string $alpsId, array $fields): array
    {
        $affordance = $this->affordance($page['body'], $alpsId);
        $contract = $this->linkContract($alpsId);
        $this->assertSame(
            $contract['path'],
            explode('?', $affordance['action'])[0],
            $alpsId . ' affordance action path drifted from #[Link] href',
        );
        $this->assertSame(
            $contract['method'],
            $this->methodOverride($affordance['action']),
            $alpsId . ' affordance method drifted from #[Link] method',
        );

        return $this->form('POST', $affordance['action'], $fields + ['csrfToken' => $this->csrfToken($page['body'])]);
    }

    /**
     * The canonical endpoint for an ALPS transition, read from the resource's
     * own #[Link] declaration (the contract the HTML must honour).
     *
     * @return array{path: string, method: string}
     */
    private function linkContract(string $rel): array
    {
        $reflection = new ReflectionMethod(Edit::class, 'onGet');
        foreach ($reflection->getAttributes(Link::class) as $attribute) {
            $link = $attribute->newInstance();
            if ($link->rel !== $rel) {
                continue;
            }

            return [
                'path' => '/' . substr($link->href, strlen('page://self/')),
                'method' => strtolower((string) $link->method),
            ];
        }

        $this->fail(sprintf('#[Link(rel: "%s")] is not declared on %s::onGet', $rel, Edit::class));
    }

    /**
     * Pull a rendered affordance (the <form> carrying data-alps="$alpsId").
     *
     * @return array{action: string, method: string}
     */
    private function affordance(string $body, string $alpsId): array
    {
        $found = preg_match('/<form\b[^>]*\bdata-alps="' . preg_quote($alpsId, '/') . '"[^>]*>/i', $body, $tag);
        $this->assertSame(1, $found, sprintf('affordance data-alps="%s" not found in rendered HTML', $alpsId));

        preg_match('/\baction="([^"]*)"/i', $tag[0], $action);
        preg_match('/\bmethod="([^"]*)"/i', $tag[0], $method);

        return [
            'action' => html_entity_decode($action[1] ?? '', ENT_QUOTES),
            'method' => strtolower($method[1] ?? 'get'),
        ];
    }

    /** The overriding method an HTML form encodes via ?_method=… (BeMart's PUT/DELETE convention). */
    private function methodOverride(string $action): string
    {
        return preg_match('/[?&]_method=([a-zA-Z]+)/', $action, $match) === 1
            ? strtolower($match[1])
            : 'post';
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
