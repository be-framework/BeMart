<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Support\Resource;

/**
 * Request-scoped Resource query/body parameters for AOP interceptors.
 *
 * BEAR.Resource resolves method arguments before Ray.Aop invokes Resource
 * methods. Once a method drops an infrastructural parameter such as
 * `csrfToken`, the interceptor can no longer read it from MethodInvocation;
 * this small stack preserves the original request query for the duration of
 * the Resource invocation, including nested Resource calls.
 */
final class RequestQueryContext
{
    /** @var list<array<string, mixed>> */
    private array $stack = [];

    /** @param array<string, mixed> $query */
    public function push(array $query): void
    {
        $this->stack[] = $query;
    }

    public function pop(): void
    {
        array_pop($this->stack);
    }

    /** @return array<string, mixed> */
    public function current(): array
    {
        if ($this->stack === []) {
            return [];
        }

        return $this->stack[array_key_last($this->stack)];
    }

    public function get(string $name): mixed
    {
        $current = $this->current();

        return $current[$name] ?? null;
    }
}
