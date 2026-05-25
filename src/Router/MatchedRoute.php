<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Router;

/**
 * The result of a successful {@see Router::match()} — the BEAR resource
 * URI and method to call plus the path parameters extracted from the
 * request path, already keyed by the resource's own parameter names.
 *
 * The front controller merges {@see $params} into the request query/body
 * so a path-segment id (`/products/detail/5`) reaches the resource the
 * same way a query param would.
 */
final class MatchedRoute
{
    /** @param array<string, string> $params Path params keyed by BEAR resource param name. */
    public function __construct(
        public readonly string $name,
        public readonly string $resource,
        public readonly string $dispatchMethod,
        public readonly array $params = [],
    ) {
    }
}
