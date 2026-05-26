<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Http;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Koriym\PhpServer\PhpServer;
use MyVendor\BeMart\Router\Route;
use MyVendor\BeMart\Router\Router;
use MyVendor\BeMart\Router\RouteTable;
use PHPUnit\Framework\TestCase;
use Throwable;

use function array_unique;
use function assert;
use function dirname;
use function escapeshellarg;
use function file_get_contents;
use function http_build_query;
use function implode;
use function in_array;
use function is_array;
use function is_string;
use function libxml_clear_errors;
use function libxml_use_internal_errors;
use function parse_url;
use function preg_match_all;
use function shell_exec;
use function sort;
use function sprintf;
use function str_starts_with;
use function strtoupper;
use function trim;

use const PHP_URL_HOST;
use const PHP_URL_PATH;
use const PHP_URL_SCHEME;

/**
 * Verifies that every rendered HTML form submits to a RouteTable route.
 *
 * HtmlLinkCrawlTest covers anchors. This companion covers browser form
 * transitions: all rendered forms must use GET/POST and their action URL
 * must be dispatchable by the shared Router. It intentionally does not submit
 * every mutation; the goal here is to catch broken browser transitions such as
 * POST forms pointing at GET-only admin aliases.
 */
final class HtmlFormActionCoverageTest extends TestCase
{
    private const HOST = '127.0.0.1:8086';
    private const BASE_URI = 'http://' . self::HOST;

    private static PhpServer|null $server = null;

    protected function setUp(): void
    {
        if (self::$server instanceof PhpServer) {
            return;
        }

        $server = new PhpServer(self::HOST, __DIR__ . '/crawl-index.php');
        $server->start();
        self::$server = $server;
    }

    public function testRenderedFormActionsAreRoutableGetOrPostTransitions(): void
    {
        $router = new Router(RouteTable::default());
        $formCount = 0;
        $problems = [];

        foreach ($this->routeTableGetUrls() as $pagePath) {
            foreach ($this->forms($pagePath) as $form) {
                $formCount++;
                $method = strtoupper($form->getAttribute('method') ?: 'GET');
                if (! in_array($method, ['GET', 'POST'], true)) {
                    $problems[] = sprintf('%s form uses unsupported method %s', $pagePath, $method);
                    continue;
                }

                $actionPath = $this->actionPath($pagePath, $form->getAttribute('action'));
                try {
                    $router->match($method, $actionPath);
                } catch (Throwable $e) {
                    $problems[] = sprintf(
                        '%s form %s %s is not routable: %s %s',
                        $pagePath,
                        $method,
                        $actionPath,
                        $e::class,
                        $e->getMessage(),
                    );
                }
            }
        }

        self::assertGreaterThan(0, $formCount, 'The HTML surface should render forms to audit.');
        self::assertSame([], $problems, implode("\n", $problems));
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
                $params[$placeholder] = $this->sampleFor($placeholder, $route->paramMap[$placeholder] ?? $placeholder);
            }
        }

        foreach ($route->queryParamMap as $wire => $canonical) {
            $params[$wire] = $this->sampleFor($wire, $canonical);
        }

        return $params;
    }

    private function sampleFor(string $wire, string $canonical): int|string
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
            'productCode' => $wire === 'id' ? 'admin-active-001' : 'sample-001',
            'resetKey' => 'valid-reset-key-pilot15-aaaa1111',
            'secretKey' => 'pending-secret-key-pilot7-2026abcd',
            'templateId' => 'tp-default-pc',
            default => match ($wire) {
                'class_name_id' => 'cn-color',
                'id', 'ids' => '1',
                'reset_key' => 'valid-reset-key-pilot15-aaaa1111',
                'secret_key' => 'pending-secret-key-pilot7-2026abcd',
                default => 'sample',
            },
        };
    }

    /** @return list<DOMElement> */
    private function forms(string $pagePath): array
    {
        $html = shell_exec(sprintf('curl -s --connect-timeout 2 --max-time 5 %s', escapeshellarg(self::BASE_URI . $pagePath)));
        if (! is_string($html) || $html === '') {
            return [];
        }

        $nodes = (new DOMXPath($this->document($html)))->query('//form');
        if ($nodes === false) {
            return [];
        }

        $forms = [];
        foreach ($nodes as $node) {
            if ($node instanceof DOMElement) {
                $forms[] = $node;
            }
        }

        return $forms;
    }

    private function actionPath(string $pagePath, string $action): string
    {
        $action = trim($action);
        if ($action === '' || $action === '#' || $action === '?') {
            return (string) (parse_url($pagePath, PHP_URL_PATH) ?: '/');
        }

        $scheme = parse_url($action, PHP_URL_SCHEME);
        if (is_string($scheme) && $scheme !== '') {
            $host = parse_url($action, PHP_URL_HOST);
            if ($host !== '127.0.0.1' && $host !== 'localhost') {
                return '/';
            }

            return (string) (parse_url($action, PHP_URL_PATH) ?: '/');
        }

        if (str_starts_with($action, '/')) {
            return (string) (parse_url($action, PHP_URL_PATH) ?: '/');
        }

        $baseDir = dirname((string) (parse_url($pagePath, PHP_URL_PATH) ?: '/'));
        if ($baseDir === '.') {
            $baseDir = '/';
        }

        return $baseDir . '/' . (string) (parse_url($action, PHP_URL_PATH) ?: $action);
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
