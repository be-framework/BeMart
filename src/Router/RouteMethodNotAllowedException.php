<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Router;

use RuntimeException;

/**
 * A route in {@see RouteTable} matches the path but not the HTTP method.
 *
 * EC-CUBE routes are method-scoped (`product_detail` is GET-only,
 * `product_add_cart` is POST-only). When the path matches but the verb
 * does not, the front controller emits 405 Method Not Allowed — keeping
 * BEAR's existing `Code` semantics at the HTTP boundary.
 */
final class RouteMethodNotAllowedException extends RuntimeException
{
}
