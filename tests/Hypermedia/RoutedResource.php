<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Hypermedia;

use Aura\Router\Route as AuraRoute;
use BEAR\Resource\Method;
use BEAR\Resource\RequestInterface;
use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Router\RouteTable;
use MyVendor\BeMart\Tests\Support\UnsupportedResourceOperationException;
use Nyholm\Psr7\ServerRequest;
use Override;

use function rtrim;
use function strtoupper;

final class RoutedResource implements ResourceInterface
{
    public function __construct(
        private readonly ResourceInterface $resource,
        private readonly RouteTable $routes,
    ) {
    }

    #[Override]
    public function newInstance($uri): ResourceObject
    {
        throw new UnsupportedResourceOperationException('newInstance is not used by workflow tests.');
    }

    #[Override]
    public function object(ResourceObject $ro): RequestInterface
    {
        throw new UnsupportedResourceOperationException('object is not used by workflow tests.');
    }

    #[Override]
    public function uri($uri): RequestInterface
    {
        throw new UnsupportedResourceOperationException('uri is not used by workflow tests.');
    }

    #[Override]
    public function newRequest(Method $method, string $uri, array $query = []): RequestInterface
    {
        throw new UnsupportedResourceOperationException('newRequest is not used by workflow tests.');
    }

    #[Override]
    public function crawl(string $uri, string $linkKey, array $query = []): ResourceObject
    {
        throw new UnsupportedResourceOperationException('crawl is not used by workflow tests.');
    }

    #[Override]
    public function href(string $rel, array $query = [], ResourceObject|null $ro = null): ResourceObject
    {
        throw new UnsupportedResourceOperationException('href is not used by workflow tests.');
    }

    #[Override]
    public function get(string $uri, array $query = []): ResourceObject
    {
        return $this->dispatch('GET', $uri, $query);
    }

    #[Override]
    public function post(string $uri, array $query = []): ResourceObject
    {
        return $this->dispatch('POST', $uri, $query);
    }

    #[Override]
    public function put(string $uri, array $query = []): ResourceObject
    {
        return $this->dispatch('PUT', $uri, $query);
    }

    #[Override]
    public function patch(string $uri, array $query = []): ResourceObject
    {
        return $this->dispatch('PATCH', $uri, $query);
    }

    #[Override]
    public function delete(string $uri, array $query = []): ResourceObject
    {
        return $this->dispatch('DELETE', $uri, $query);
    }

    #[Override]
    public function head(string $uri, array $query = []): ResourceObject
    {
        throw new UnsupportedResourceOperationException('head is not used by workflow tests.');
    }

    #[Override]
    public function options(string $uri, array $query = []): ResourceObject
    {
        throw new UnsupportedResourceOperationException('options is not used by workflow tests.');
    }

    /** @param array<string, mixed> $query */
    private function dispatch(string $method, string $uri, array $query): ResourceObject
    {
        $method = strtoupper($method);
        $route = $this->routes->matcher()->match(new ServerRequest($method, self::normalizeRoutePath($uri)));
        if (! $route instanceof AuraRoute) {
            throw new UnsupportedResourceOperationException('No workflow route matches ' . $method . ' ' . $uri);
        }

        $metadata = RouteTable::metadataFor($route, $method);
        $params = RouteTable::resourceParams($route, $metadata) + $query;

        return match ($metadata['dispatchMethod']) {
            'get' => $this->resource->get($metadata['resource'], $params),
            'post' => $this->resource->post($metadata['resource'], $params),
            'put' => $this->resource->put($metadata['resource'], $params),
            'patch' => $this->resource->patch($metadata['resource'], $params),
            'delete' => $this->resource->delete($metadata['resource'], $params),
            default => throw new UnsupportedResourceOperationException(
                'Unsupported workflow dispatch method: ' . $metadata['dispatchMethod'],
            ),
        };
    }

    private static function normalizeRoutePath(string $path): string
    {
        if ($path === '' || $path === '/') {
            return '/';
        }

        $trimmed = rtrim($path, '/');

        return $trimmed === '' ? '/' : $trimmed;
    }
}
