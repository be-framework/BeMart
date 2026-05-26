<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Http;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Koriym\PhpServer\PhpServer;
use MyVendor\BeMart\Router\Route;
use MyVendor\BeMart\Router\RouteTable;
use PHPUnit\Framework\TestCase;

use function array_key_exists;
use function array_unique;
use function assert;
use function escapeshellarg;
use function explode;
use function file_put_contents;
use function http_build_query;
use function implode;
use function in_array;
use function is_array;
use function is_string;
use function json_encode;
use function libxml_clear_errors;
use function libxml_use_internal_errors;
use function parse_url;
use function preg_match;
use function preg_match_all;
use function preg_split;
use function shell_exec;
use function sort;
use function sprintf;
use function str_contains;
use function str_ends_with;
use function str_starts_with;
use function strtolower;
use function sys_get_temp_dir;
use function tempnam;
use function trim;

use const FILE_APPEND;
use const JSON_THROW_ON_ERROR;
use const PHP_EOL;
use const PHP_URL_HOST;
use const PHP_URL_PATH;
use const PHP_URL_SCHEME;

/**
 * End-to-end HTML crawl over the RouteTable GET surface and the local links
 * rendered from those pages.
 *
 * This is intentionally HTTP-level rather than Resource-level: it exercises
 * public/index.php, Bootstrap, RouteTable, Twig rendering, PHP sessions and
 * the generated hrefs together. The companion crawl-index.php seeds stable
 * fake customer/admin identities so authenticated screens can be reached
 * without depending on a previous browser session.
 */
final class HtmlLinkCrawlTest extends TestCase
{
    private const HOST = '127.0.0.1:8082';
    private const BASE_URI = 'http://' . self::HOST;
    private const MAX_PAGES = 400;

    /** @var list<string> */
    private const FORBIDDEN_BODY_NEEDLES = [
        'Fatal error',
        'Uncaught',
        'Method Not Allowed',
        'Unknown APP_CONTEXT',
        '未実装',
        '実装していません',
    ];

    private static PhpServer|null $server = null;

    private string $cookieJar;
    private string $logFile;

    protected function setUp(): void
    {
        $this->cookieJar = (string) tempnam(sys_get_temp_dir(), 'bemart-crawl-cookie-');
        $this->logFile = __DIR__ . '/log/html-link-crawl.log';
        file_put_contents($this->logFile, '');
        $this->startServer();
    }

    public function testRouteTableGetPagesAndRenderedLocalLinksAreReachable(): void
    {
        $queue = $this->routeTableGetUrls();
        $seen = [];
        $problems = [];

        while ($queue !== []) {
            $url = array_shift($queue);
            if (! is_string($url) || isset($seen[$url])) {
                continue;
            }

            $seen[$url] = true;
            $response = $this->get($url);
            foreach ($this->problemsFor($url, $response) as $problem) {
                $problems[] = $problem;
            }

            if ($response['status'] === 0) {
                break;
            }

            if ($response['status'] !== 200 || count($seen) >= self::MAX_PAGES) {
                continue;
            }

            foreach ($this->localLinks($response['body']) as $href) {
                if (! isset($seen[$href]) && ! in_array($href, $queue, true)) {
                    $queue[] = $href;
                }
            }
        }

        $this->assertSame(
            [],
            $problems,
            sprintf(
                "HTML crawl found %d problem(s) after visiting %d page(s):\n%s\n\nSee %s",
                count($problems),
                count($seen),
                implode("\n", $problems),
                $this->logFile,
            ),
        );
    }

    public function testCoreCssAndJsAssetsAreServedByTheHttpRouter(): void
    {
        $assets = [
            '/assets/css/style.css' => ['text/css', 'normalize.css'],
            '/assets/css/customize.css' => ['text/css', 'カスタマイズ用CSS'],
            '/template/admin/assets/css/app.css' => ['text/css', 'background: #eff0f4'],
            '/bundle/front.bundle.js' => ['javascript', 'front.bundle.js.LICENSE.txt'],
            '/bundle/admin.bundle.js' => ['javascript', 'admin.bundle.js.LICENSE.txt'],
        ];

        foreach ($assets as $path => [$contentTypeNeedle, $bodyNeedle]) {
            $response = $this->get($path);

            self::assertSame(200, $response['status'], $path);
            self::assertStringContainsString($contentTypeNeedle, strtolower(implode("\n", $response['headers'])), $path);
            self::assertStringContainsString($bodyNeedle, $response['body'], $path);
            self::assertStringNotContainsString('<html', strtolower($response['body']), $path);
        }
    }

    private function startServer(): void
    {
        if (self::$server instanceof PhpServer) {
            return;
        }

        $server = new PhpServer(self::HOST, __DIR__ . '/crawl-index.php');
        $server->start();
        self::$server = $server;
    }

    /** @return list<string> */
    private function routeTableGetUrls(): array
    {
        $urls = [];
        foreach (RouteTable::default()->routes as $route) {
            if (! in_array('GET', $route->methods, true)) {
                continue;
            }

            $urls[] = $route->generate($this->sampleParams($route));
        }

        $urls = array_values(array_unique($urls));
        sort($urls);

        return $urls;
    }

    /** @return array<string, int|string> */
    private function sampleParams(Route $route): array
    {
        $params = [];
        if (preg_match_all('/\{([^}]+)\}/', $route->path, $matches) === 1) {
            foreach ($matches[1] as $placeholder) {
                $params[$placeholder] = $this->sampleForWireName($placeholder, $route->paramMap[$placeholder] ?? $placeholder);
            }
        }

        foreach ($route->queryParamMap as $wire => $canonical) {
            $params[$wire] = $this->sampleForWireName($wire, $canonical);
        }

        return $params;
    }

    private function sampleForWireName(string $wire, string $canonical): int|string
    {
        return match ($canonical) {
            'addressId' => 'addr00000000000000000000000000a1',
            'blockId' => 'bk-header',
            'categoryId' => 'cat-food',
            'classCategoryId' => 'cc-red',
            'classNameId' => 'cn-color',
            'customerId' => '0123456789abcdef0123456789abcdef',
            'deliveryId' => 'del-yamato',
            'layoutId' => 'lo-pc-default',
            'loginId' => 'test-admin',
            'newsId' => 'nw-welcome',
            'orderNo' => 'past0000000000000000000000000001',
            'pageId' => 'pg-homepage',
            'paymentId' => 'pay-cod',
            'productCode' => $wire === 'id' && str_contains($canonical, 'product') ? 'admin-active-001' : 'sample-001',
            'resetKey' => 'valid-reset-key-pilot15-aaaa1111',
            'secretKey' => 'pending-secret-key-pilot7-2026abcd',
            'templateId' => 'tp-default-pc',
            default => $this->fallbackSample($wire),
        };
    }

    private function fallbackSample(string $wire): int|string
    {
        return match ($wire) {
            'class_name_id' => 'cn-color',
            'id', 'ids' => '1',
            'reset_key' => 'valid-reset-key-pilot15-aaaa1111',
            'secret_key' => 'pending-secret-key-pilot7-2026abcd',
            default => 'sample',
        };
    }

    /**
     * @return array{status:int, headers:list<string>, body:string}
     */
    private function get(string $path): array
    {
        $url = self::BASE_URI . $path;
        $jar = escapeshellarg($this->cookieJar);
        $command = sprintf('curl -s -i --connect-timeout 2 --max-time 5 -b %s -c %s %s', $jar, $jar, escapeshellarg($url));
        $raw = shell_exec($command);
        if (! is_string($raw) || $raw === '') {
            return ['status' => 0, 'headers' => [], 'body' => ''];
        }

        [$headers, $body] = $this->splitResponse($raw);
        $status = $this->statusCode($headers);
        file_put_contents(
            $this->logFile,
            sprintf("GET %s => %d\n%s\n\n", $path, $status, implode(PHP_EOL, $headers)),
            FILE_APPEND,
        );

        return ['status' => $status, 'headers' => $headers, 'body' => $body];
    }

    /** @return array{0:list<string>, 1:string} */
    private function splitResponse(string $raw): array
    {
        $parts = preg_split("/\r?\n\r?\n/", $raw, 2);
        if (! is_array($parts) || ! array_key_exists(1, $parts)) {
            return [[], $raw];
        }

        $headers = preg_split("/\r?\n/", trim($parts[0]));
        assert(is_array($headers));

        return [$headers, $parts[1]];
    }

    /** @param list<string> $headers */
    private function statusCode(array $headers): int
    {
        foreach ($headers as $header) {
            if (preg_match('/\s(\d{3})\s/', $header . ' ', $match) === 1) {
                return (int) $match[1];
            }
        }

        return 0;
    }

    /**
     * @param array{status:int, headers:list<string>, body:string} $response
     * @return list<string>
     */
    private function problemsFor(string $url, array $response): array
    {
        $problems = [];
        if (! in_array($response['status'], [200, 303], true)) {
            $problems[] = sprintf('%s returned HTTP %d', $url, $response['status']);
        }

        foreach (self::FORBIDDEN_BODY_NEEDLES as $needle) {
            if (str_contains($response['body'], $needle)) {
                $problems[] = sprintf('%s body contains forbidden text: %s', $url, $needle);
            }
        }

        return $problems;
    }

    /** @return list<string> */
    private function localLinks(string $html): array
    {
        $xpath = new DOMXPath($this->document($html));
        $nodes = $xpath->query('//a[@href]');
        if ($nodes === false) {
            return [];
        }

        $links = [];
        foreach ($nodes as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }

            $href = $this->normalizeHref($node->getAttribute('href'));
            if ($href === null) {
                continue;
            }

            $links[] = $href;
        }

        $links = array_values(array_unique($links));
        sort($links);

        return $links;
    }

    private function normalizeHref(string $href): string|null
    {
        $href = trim($href);
        if ($href === '' || str_starts_with($href, '#')) {
            return null;
        }

        $lower = strtolower($href);
        foreach (['javascript:', 'mailto:', 'tel:', 'data:'] as $scheme) {
            if (str_starts_with($lower, $scheme)) {
                return null;
            }
        }

        $scheme = parse_url($href, PHP_URL_SCHEME);
        if (is_string($scheme) && $scheme !== '') {
            $host = parse_url($href, PHP_URL_HOST);
            if ($host !== '127.0.0.1' && $host !== 'localhost') {
                return null;
            }

            $path = parse_url($href, PHP_URL_PATH);
            return is_string($path) && $path !== '' ? $path : '/';
        }

        if (! str_starts_with($href, '/')) {
            return null;
        }

        if ($this->isStaticAsset($href)) {
            return null;
        }

        return $href;
    }

    private function isStaticAsset(string $href): bool
    {
        foreach (['/assets/', '/html/', '/user_data/', '/template/'] as $prefix) {
            if (str_starts_with($href, $prefix)) {
                return true;
            }
        }

        foreach (['.css', '.js', '.png', '.jpg', '.jpeg', '.gif', '.svg', '.ico', '.webp', '.woff', '.woff2'] as $suffix) {
            if (str_ends_with(strtolower($href), $suffix)) {
                return true;
            }
        }

        return false;
    }

    private function document(string $html): DOMDocument
    {
        $previous = libxml_use_internal_errors(true);
        $document = new DOMDocument();
        $document->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return $document;
    }
}
