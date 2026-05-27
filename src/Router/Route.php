<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Router;

/**
 * One BeMart route metadata entry.
 *
 * Aura.Router owns mechanics: path matching, placeholder extraction, HTTP
 * method matching, and path generation. This value object keeps only the
 * metadata Aura does not know: EC-CUBE route alias, BEAR resource URI, optional
 * internal dispatch method, and parameter-name mappings.
 */
final class Route
{
    /**
     * @param string               $name      EC-CUBE route name (e.g. `product_detail`).
     * @param list<string>          $methods   Upper-case HTTP verbs this route serves publicly.
     * @param string               $path      EC-CUBE URL pattern with `{placeholder}` segments.
     * @param string               $resource  BEAR resource URI (e.g. `page://self/product`).
     * @param array<string,string> $paramMap       EC-CUBE placeholder name => BEAR resource param name.
     * @param string|null          $dispatchMethod Internal BEAR resource method, defaults to the HTTP method.
     * @param array<string,string> $defaults       Default params merged into a successful match.
     * @param array<string,string> $queryParamMap  EC-CUBE query/form param name => BEAR resource param name.
     */
    public function __construct(
        public readonly string $name,
        public readonly array $methods,
        public readonly string $path,
        public readonly string $resource,
        public readonly array $paramMap = [],
        public readonly string|null $dispatchMethod = null,
        public readonly array $defaults = [],
        public readonly array $queryParamMap = [],
    ) {
    }
}
