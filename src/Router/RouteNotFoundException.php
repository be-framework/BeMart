<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Router;

use RuntimeException;

/**
 * No route in {@see RouteTable} matches the requested path.
 *
 * The front controller catches this and emits a clean 404 instead of
 * letting an unmapped path fall through to BEAR and surface as an
 * uncaught {@see \Ray\Di\Exception\Unbound} (which leaked a 200 + stack
 * trace before the router landed).
 *
 * A dedicated class (not a generic exception) so the entry point can
 * `catch` precisely this condition without swallowing real 500s.
 */
final class RouteNotFoundException extends RuntimeException
{
}
