<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Http;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Aura\Router\Generator as AuraGenerator;
use Aura\Router\Map;
use Aura\Router\Route as AuraRoute;
use Aura\Router\RouterContainer;
use Koriym\PhpServer\PhpServer;
use MyVendor\BeMart\Module\BeMartTwigExtension;
use MyVendor\BeMart\Support\Router\AuraRouter;
use PHPUnit\Framework\TestCase;

use function array_key_exists;
use function array_unique;
use function assert;
use function dirname;
use function escapeshellarg;
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
 * Verifies that every rendered HTML form submits to an Aura route.
 *
 * HtmlLinkCrawlTest covers anchors. This companion covers browser form
 * transitions: all rendered forms must use GET/POST and their action URL
 * must be dispatchable by the shared AuraRouter. It intentionally does not submit
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
        $routes = $this->routerContainer();
        $router = new AuraRouter($routes);
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
                $match = $router->match(
                    ['_GET' => [], '_POST' => []],
                    ['REQUEST_METHOD' => $method, 'REQUEST_URI' => $actionPath],
                );
                if ($match->path === '') {
                    $problems[] = sprintf(
                        '%s form %s %s is not routable',
                        $pagePath,
                        $method,
                        $actionPath,
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
        $routes = $this->routerContainer();
        $urls = [];
        $urlsHelper = new BeMartTwigExtension($routes);
        foreach ($routes->getMap()->getRoutes() as $route) {
            /** @var mixed $methods */
            $methods = $route->extras['bemart']['methods'] ?? [];
            if (! is_array($methods) || ! array_key_exists('GET', $methods)) {
                continue;
            }

            /** @var mixed $metadata */
            $metadata = $methods['GET'];
            if (! is_array($metadata)) {
                continue;
            }

            $urls[] = $urlsHelper->path((string) $route->name, $this->sampleParams($route, $metadata));
        }

        $urls = array_values(array_unique($urls));
        sort($urls);

        return $urls;
    }

    /**
     * @param array<string, mixed> $metadata
     * @return array<string, int|string>
     */
    private function sampleParams(AuraRoute $route, array $metadata): array
    {
        $params = [];
        /** @var array<string, string> $paramMap */
        $paramMap = is_array($metadata['paramMap'] ?? null) ? $metadata['paramMap'] : [];
        if (preg_match_all(AuraGenerator::REGEX, (string) $route->path, $matches) === 1) {
            foreach ($matches[1] as $placeholder) {
                $params[$placeholder] = $this->sampleFor($placeholder, $paramMap[$placeholder] ?? $placeholder);
            }
        }

        /** @var array<string, string> $queryParamMap */
        $queryParamMap = is_array($metadata['queryParamMap'] ?? null) ? $metadata['queryParamMap'] : [];
        foreach ($queryParamMap as $wire => $canonical) {
            $params[$wire] = $this->sampleFor($wire, $canonical);
        }

        return $params;
    }

    private function routerContainer(): RouterContainer
    {
        $container = new RouterContainer();
        /** @var callable(Map): null $routes */
        $routes = require __DIR__ . '/../../config/aura-routes.php';
        $container->setMapBuilder($routes);

        return $container;
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
            'orderNos' => 'past0000000000000000000000000001',
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
