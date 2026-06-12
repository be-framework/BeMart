<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Support\Router;

use BEAR\Sunday\Extension\Router\RouterInterface;
use BEAR\Sunday\Extension\Router\RouterMatch;
use Override;

use function explode;
use function file_get_contents;
use function is_array;
use function is_string;
use function json_decode;
use function parse_str;
use function parse_url;
use function preg_match;
use function rawurldecode;
use function rtrim;
use function str_contains;
use function str_replace;
use function strpos;
use function substr;
use function strtolower;

use const PHP_URL_PATH;
use const PHP_URL_QUERY;
use const UPLOAD_ERR_OK;

/**
 * Canonical BEAR resource router.
 *
 * The public HTTP URL is the BEAR Resource path. IDEA STORE keeps a
 * narrow storefront compatibility layer for EC-CUBE-shaped inbound URLs
 * while generated links use canonical resource paths.
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
        [$path, $legacyParams] = $this->legacyStorefrontRoute($path);
        $params += $legacyParams;

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
        if (! isset($body['csv'])) {
            $body += $this->uploadedCsv($globals);
        }

        return $body + $get;
    }

    /**
     * @param array<string, mixed> $globals
     * @return array{csv?: string}
     */
    private function uploadedCsv(array $globals): array
    {
        /** @var mixed $files */
        $files = $globals['_FILES'] ?? [];
        if (! is_array($files)) {
            return [];
        }

        /** @var mixed $file */
        $file = $files['import_file'] ?? null;
        if (! is_array($file)) {
            return [];
        }

        /** @var mixed $error */
        $error = $file['error'] ?? null;
        if ((int) $error !== UPLOAD_ERR_OK) {
            return [];
        }

        /** @var mixed $tmpName */
        $tmpName = $file['tmp_name'] ?? null;
        if (! is_string($tmpName) || $tmpName === '') {
            return [];
        }

        $csv = file_get_contents($tmpName);
        if (! is_string($csv) || $csv === '') {
            return [];
        }

        return ['csv' => $csv];
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
        $queryString = $this->cliQueryString($target);
        $query = $this->parseCliQueryString($queryString);

        return [strtolower((string) $argv[1]), $target, $query];
    }



    private function cliQueryString(string $target): string
    {
        $question = strpos($target, '?');
        if ($question === false) {
            return '';
        }

        return substr($target, $question + 1);
    }

    /** @return array<string, string> */
    private function parseCliQueryString(string $queryString): array
    {
        if ($queryString === '') {
            return [];
        }

        $query = [];
        foreach (explode('&', $queryString) as $pair) {
            if ($pair === '') {
                continue;
            }

            [$key, $value] = explode('=', $pair, 2) + [1 => ''];
            $key = rawurldecode(str_replace('+', ' ', $key));
            if ($key === '') {
                continue;
            }

            $query[$key] = rawurldecode(str_replace('+', ' ', $value));
        }

        return $query;
    }


    /**
     * @return array{0: string, 1: array<string, string>}
     */
    private function legacyStorefrontRoute(string $path): array
    {
        if ($path === '/products/list') {
            return ['/products', []];
        }

        if ($path === '/shopping/nonmember') {
            return ['/shopping/non-member', []];
        }

        if (preg_match('#^/products/detail/([^/]+)$#', $path, $matches) === 1) {
            return ['/product', ['productCode' => rawurldecode($matches[1])]];
        }

        if (preg_match('#^/products/add_cart/([^/]+)$#', $path, $matches) === 1) {
            return ['/cart/item', ['productCode' => rawurldecode($matches[1])]];
        }

        return [$path, []];
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
