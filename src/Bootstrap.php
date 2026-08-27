<?php

declare(strict_types=1);

namespace MyVendor\BeMart;

use BEAR\Resource\ResourceObject;
use BEAR\QueryRepository\UriScopedHttpCacheInterface;
use BEAR\Resource\Uri;
use BEAR\Sunday\Extension\Application\AppInterface;
use BEAR\Sunday\Extension\Router\RouterInterface;
use MyVendor\BeMart\Auth\HtmlAdminSessionAdapter;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Module\App;
use Ray\Di\AbstractModule;
use Throwable;

use function assert;
use function http_build_query;
use function putenv;

/**
 * @psalm-import-type Globals from RouterInterface
 * @psalm-import-type Server from RouterInterface
 */
final class Bootstrap
{
    /**
     * @param non-empty-string    $context
     * @param Globals             $globals
     * @param Server              $server
     * @param AbstractModule|null $override dev-only DI override (e.g. dev login); MUST be null in prod
     *
     * @return 0|1
     */
    public function __invoke(string $context, array $globals, array $server, AbstractModule|null $override = null): int
    {
        putenv('APP_CONTEXT=' . $context);

        $injector = $override !== null
            ? Injector::getOverrideInstance($context, $override)
            : Injector::getInstance($context);

        $app = $injector->getInstance(AppInterface::class);
        assert($app instanceof App);
        /** @var array{HTTP_IF_NONE_MATCH?: string} $cacheServer */
        $cacheServer = $server;
        // Route first, then answer the conditional request: an entity-tag belongs to one resource,
        // and the unscoped question ("is this validator alive anywhere?") answers 304 to a client
        // returning a validator it holds for another URI. Matching a path is not what a 304 saves.
        $request = $app->router->match($globals, $server);
        $notModified = $app->httpCache instanceof UriScopedHttpCacheInterface
            ? $app->httpCache->isNotModifiedFor(new Uri($request->path . ($request->query === [] ? '' : '?' . http_build_query($request->query))), $cacheServer)
            : $app->httpCache->isNotModified($cacheServer);
        if ($notModified) {
            $app->httpCache->transfer();

            return 0;
        }

        try {
            $adminSession = $injector->getInstance(AdminSession::class);
            if ($adminSession instanceof HtmlAdminSessionAdapter) {
                $adminSession->refresh();
            }

            $response = $app->resource->{$request->method}->uri($request->path)($request->query);
            assert($response instanceof ResourceObject);
            $response->transfer($app->responder, $server);

            return 0;
        } catch (Throwable $e) {
            $app->throwableHandler->handle($e, $request)->transfer();

            return 1;
        }
    }
}
