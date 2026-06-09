<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Support\Router;

use BEAR\Sunday\Extension\Router\RouterInterface;
use BEAR\Sunday\Extension\Router\RouterMatch;
use Override;

use function file_get_contents;
use function is_array;
use function is_string;
use function json_decode;
use function parse_str;
use function parse_url;
use function rtrim;
use function str_contains;
use function strtolower;

use const PHP_URL_PATH;
use const PHP_URL_QUERY;

/**
 * Canonical BEAR resource router.
 *
 * The public HTTP URL is the BEAR Resource path. There are no EC-CUBE route
 * names, path parameters, aliases, or compatibility redirects here.
 */
final class CanonicalResourceRouter implements RouterInterface
{
    /** {@inheritDoc} */
    #[Override]
    public function match(array $globals, array $server): RouterMatch
    {
        [$method, $target, $cliQuery] = $this->requestTarget($globals, $server);
        $path = $this->normalizePath((string) (parse_url($target, PHP_URL_PATH) ?? '/'));
        $params = $this->requestParams($method, $globals, $server) + $cliQuery;

        if ($method === 'post') {
            $override = strtolower((string) ($params['_method'] ?? ''));
            unset($params['_method']);
            if ($override === 'put' || $override === 'delete') {
                $method = $override;
            }
        }

        $resourcePath = $path === '/' ? 'page://self/index' : 'page://self' . $path;

        return new RouterMatch($method, $resourcePath, $params);
    }

    /** {@inheritDoc} */
    #[Override]
    public function generate($name, $data)
    {
        return false;
    }

    /**
     * @param array<string, mixed> $globals
     * @return array<string, mixed>
     */
    private function requestParams(string $method, array $globals, array $server): array
    {
        /** @var mixed $get */
        $get = $globals['_GET'] ?? [];
        /** @var mixed $post */
        $post = $globals['_POST'] ?? [];
        $get = is_array($get) ? $get : [];
        $post = is_array($post) ? $post : [];

        if ($method === 'get' || $method === 'head') {
            return $get;
        }

        $body = $post !== [] ? $post : $this->jsonBody($server);

        return $body + $get;
    }

    /**
     * @param array<string, mixed> $globals
     * @param array<string, mixed> $server
     * @return array{0: string, 1: string, 2: array<string, mixed>}
     */
    private function requestTarget(array $globals, array $server): array
    {
        if (isset($server['REQUEST_METHOD'], $server['REQUEST_URI'])) {
            return [
                strtolower((string) $server['REQUEST_METHOD']),
                (string) $server['REQUEST_URI'],
                [],
            ];
        }

        /** @var mixed $argv */
        $argv = $globals['argv'] ?? $server['argv'] ?? [];
        if (! is_array($argv) || ! isset($argv[1], $argv[2])) {
            return ['get', '/', []];
        }

        $target = (string) $argv[2];
        $query = [];
        $queryString = (string) (parse_url($target, PHP_URL_QUERY) ?? '');
        if ($queryString !== '') {
            parse_str($queryString, $query);
        }

        /** @var array<string, mixed> $query */
        return [strtolower((string) $argv[1]), $target, $query];
    }

    /** @param array<string, mixed> $server */
    private function jsonBody(array $server): array
    {
        $contentType = (string) ($server['CONTENT_TYPE'] ?? $server['HTTP_CONTENT_TYPE'] ?? '');
        if (! str_contains($contentType, 'application/json')) {
            return [];
        }

        $raw = $server['HTTP_RAW_POST_DATA'] ?? file_get_contents('php://input');
        if (! is_string($raw) || $raw === '') {
            return [];
        }

        /** @var mixed $decoded */
        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function normalizePath(string $path): string
    {
        if ($path === '' || $path === '/') {
            return '/';
        }

        $trimmed = rtrim($path, '/');

        return $trimmed === '' ? '/' : $trimmed;
    }
}
