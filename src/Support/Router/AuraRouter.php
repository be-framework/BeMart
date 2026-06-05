<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Support\Router;

use Aura\Router\Exception\RouteNotFound as AuraRouteNotFound;
use Aura\Router\Route as AuraRoute;
use Aura\Router\RouterContainer;
use BEAR\Sunday\Extension\Router\NullMatch;
use BEAR\Sunday\Extension\Router\RouterInterface;
use BEAR\Sunday\Extension\Router\RouterMatch;
use Nyholm\Psr7\ServerRequest;
use Override;

use function array_key_exists;
use function array_values;
use function is_array;
use function parse_url;
use function rtrim;
use function str_ends_with;
use function strtolower;
use function strtoupper;

use const PHP_URL_PATH;

/**
 * BEAR RouterInterface adapter for BeMart's EC-CUBE Aura route map.
 *
 * Aura owns path matching, placeholder extraction, and URL generation. This
 * adapter only translates the matched Aura route metadata into BEAR's
 * RouterMatch so Bootstrap can dispatch through AppInterface like other
 * BEAR.Sunday applications.
 */
final class AuraRouter implements RouterInterface
{
    public function __construct(private readonly RouterContainer $routes)
    {
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function match(array $globals, array $server): RouterMatch
    {
        $method = strtoupper((string) ($server['REQUEST_METHOD'] ?? 'GET'));
        $target = (string) ($server['REQUEST_URI'] ?? '/');
        $path = (string) (parse_url($target, PHP_URL_PATH) ?? '/');
        $route = $this->routes->getMatcher()->match(new ServerRequest($method, $this->normalizeRoutePath($path)));
        if (! $route instanceof AuraRoute) {
            return new NullMatch();
        }

        $metadata = $this->routeMetadata($route, $method);
        if ($metadata === null) {
            return new NullMatch();
        }

        $params = $this->resourceParams($route, $metadata) + $this->requestParams(strtolower($method), $globals);
        $this->normalizeWireAliases($params);
        $this->normalizeRouteAliases($metadata['queryParamMap'], $params);

        return new RouterMatch($metadata['dispatchMethod'], $metadata['resource'], $params);
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function generate($name, $data)
    {
        try {
            return $this->routes->getGenerator()->generate($name, $data);
        } catch (AuraRouteNotFound) {
            return false;
        }
    }

    /**
     * @return array{
     *     resource: string,
     *     dispatchMethod: string,
     *     paramMap: array<string, string>,
     *     defaults: array<string, mixed>,
     *     queryParamMap: array<string, string>
     * }|null
     */
    private function routeMetadata(AuraRoute $route, string $method): array|null
    {
        /** @var mixed $metadata */
        $metadata = $route->extras['bemart']['methods'][$method] ?? null;
        if (! is_array($metadata)) {
            return null;
        }

        /** @var array{resource: string, dispatchMethod: string, paramMap: array<string, string>, defaults: array<string, mixed>, queryParamMap: array<string, string>} */
        return $metadata;
    }

    /**
     * @param array{
     *     resource: string,
     *     dispatchMethod: string,
     *     paramMap: array<string, string>,
     *     defaults: array<string, mixed>,
     *     queryParamMap: array<string, string>
     * } $metadata
     * @return array<string, mixed>
     */
    private function resourceParams(AuraRoute $route, array $metadata): array
    {
        $params = $metadata['defaults'];
        /** @var array<string, mixed> $attributes */
        $attributes = $route->attributes;
        foreach ($attributes as $key => $value) {
            $resourceParam = $metadata['paramMap'][$key] ?? $key;
            $params[$resourceParam] = (string) $value;
        }

        return $params;
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

    /** Strip a trailing slash, but never reduce the site root to empty. */
    private function normalizeRoutePath(string $path): string
    {
        if ($path === '' || $path === '/') {
            return '/';
        }

        $trimmed = rtrim($path, '/');

        return $trimmed === '' ? '/' : $trimmed;
    }

    /** @param array<string, mixed> $params */
    private function normalizeWireAliases(array &$params): void
    {
        $wireAliases = [
            '_token' => 'csrfToken',
            '_csrf_token' => 'csrfToken',
            'product_id' => 'productCode',
            'login_id' => 'loginId',
            'login_email' => 'email',
            'login_pass' => 'password',
            'tracking_number' => 'trackingNumber',
        ];
        foreach ($wireAliases as $wire => $canonical) {
            if (array_key_exists($wire, $params) && ! array_key_exists($canonical, $params)) {
                $params[$canonical] = $params[$wire];
                unset($params[$wire]);
            }
        }
    }

    /**
     * @param array<string, string> $queryParamMap
     * @param array<string, mixed>  $params
     */
    private function normalizeRouteAliases(array $queryParamMap, array &$params): void
    {
        foreach ($queryParamMap as $wire => $canonical) {
            if (array_key_exists($wire, $params) && ! array_key_exists($canonical, $params)) {
                $value = $params[$wire];
                if (is_array($value) && ! $this->isListParam($canonical)) {
                    $values = array_values($value);
                    $value = $values[0] ?? null;
                }

                $params[$canonical] = $value;
                unset($params[$wire]);
            }
        }
    }

    private function isListParam(string $param): bool
    {
        return $param === 'columns'
            || str_ends_with($param, 'Nos')
            || str_ends_with($param, 'Codes');
    }
}
