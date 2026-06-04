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

    /** @param array<string, mixed> $query */
    protected function follow(ResourceObject $response, string $rel, array $query = []): ResourceObject
    {
        $next = $this->resource->href($rel, $query, $response);
        $this->assertSame(Code::OK, $next->code);

        return $next;
    }

    protected function bodyValue(ResourceObject $response, string $key): mixed
    {
        $body = $response->body;
        $this->assertIsArray($body);
        $this->assertArrayHasKey($key, $body);

        return $body[$key];
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
