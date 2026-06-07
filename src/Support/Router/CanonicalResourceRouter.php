<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Support\Router;

use BEAR\Sunday\Extension\Router\RouterInterface;
use BEAR\Sunday\Extension\Router\RouterMatch;
use Override;

use function is_array;
use function parse_url;
use function rtrim;
use function strtolower;

use const PHP_URL_PATH;

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
        $method = strtolower((string) ($server['REQUEST_METHOD'] ?? 'get'));
        $target = (string) ($server['REQUEST_URI'] ?? '/');
        $path = $this->normalizePath((string) (parse_url($target, PHP_URL_PATH) ?? '/'));
        $params = $this->requestParams($method, $globals);

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
    private function requestParams(string $method, array $globals): array
    {
        /** @var mixed $get */
        $get = $globals['_GET'] ?? [];
        /** @var mixed $post */
        $post = $globals['_POST'] ?? [];
        $get = is_array($get) ? $get : [];
        $post = is_array($post) ? $post : [];

        return $method === 'get' || $method === 'head' ? $get : $post + $get;
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
