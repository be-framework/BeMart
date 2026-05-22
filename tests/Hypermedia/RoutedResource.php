<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Hypermedia;

use BEAR\Resource\Method;
use BEAR\Resource\RequestInterface;
use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Router\Router;
use MyVendor\BeMart\Tests\Support\UnsupportedResourceOperationException;
use Override;

final class RoutedResource implements ResourceInterface
{
    public function __construct(
        private readonly ResourceInterface $resource,
        private readonly Router $router,
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
        $matched = $this->router->match('GET', $uri);

        return $this->resource->get($matched->resource, $matched->params + $query);
    }

    #[Override]
    public function post(string $uri, array $query = []): ResourceObject
    {
        $matched = $this->router->match('POST', $uri);

        return $this->resource->post($matched->resource, $matched->params + $query);
    }

    #[Override]
    public function put(string $uri, array $query = []): ResourceObject
    {
        $matched = $this->router->match('PUT', $uri);

        return $this->resource->put($matched->resource, $matched->params + $query);
    }

    #[Override]
    public function patch(string $uri, array $query = []): ResourceObject
    {
        $matched = $this->router->match('PATCH', $uri);

        return $this->resource->patch($matched->resource, $matched->params + $query);
    }

    #[Override]
    public function delete(string $uri, array $query = []): ResourceObject
    {
        $matched = $this->router->match('DELETE', $uri);

        return $this->resource->delete($matched->resource, $matched->params + $query);
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
}
