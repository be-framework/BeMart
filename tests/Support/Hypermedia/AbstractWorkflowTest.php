<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Support\Hypermedia;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;
use PHPUnit\Framework\TestCase;

use function is_string;
use function strtolower;

abstract class AbstractWorkflowTest extends TestCase
{
    /** @var array<class-string, ResourceInterface> */
    private static array $resources = [];
    protected ResourceInterface $resource;

    protected function setUp(): void
    {
        $this->resource = self::$resources[static::class] ??= $this->newResource();
    }

    public static function tearDownAfterClass(): void
    {
        unset(self::$resources[static::class]);
    }

    abstract protected function newResource(): ResourceInterface;

    protected function follow(ResourceObject $response, string $rel): ResourceObject
    {
        $next = $this->resource->get($this->link($response, $rel));
        $this->assertSame(Code::OK, $next->code);

        return $next;
    }

    /** @param array<string, mixed> $query */
    protected function post(ResourceObject $response, array $query): ResourceObject
    {
        return $this->resource->post($this->unsafeTarget($response, 'POST'), $query);
    }

    protected function link(ResourceObject $response, string $rel): string
    {
        $body = $response->body;
        $this->assertIsArray($body);
        $links = $body['links'] ?? null;
        $this->assertIsArray($links);
        $href = $links[$rel] ?? null;
        $this->assertIsString($href);

        return $href;
    }

    private function unsafeTarget(ResourceObject $response, string $method): string
    {
        $body = $response->body;
        $this->assertIsArray($body);
        $submitTo = $body['submitTo'] ?? null;
        $this->assertIsArray($submitTo);
        $this->assertSame($method, $submitTo['method'] ?? null);
        $href = $submitTo['href'] ?? null;
        $this->assertIsString($href);

        return $href;
    }

    protected function bodyString(ResourceObject $response, string $key): string
    {
        $body = $response->body;
        $this->assertIsArray($body);
        $value = $body[$key] ?? null;
        $this->assertIsString($value);

        return $value;
    }

    protected function header(ResourceObject $response, string $name): string|null
    {
        foreach ($response->headers as $header => $value) {
            if (! is_string($header) || ! is_string($value)) {
                continue;
            }

            if (strtolower($header) !== strtolower($name)) {
                continue;
            }

            return $value;
        }

        return null;
    }
}
