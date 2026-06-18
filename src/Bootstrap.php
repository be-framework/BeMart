<?php

declare(strict_types=1);

namespace MyVendor\BeMart;

use BEAR\Resource\ResourceObject;
use BEAR\Sunday\Extension\Application\AppInterface;
use BEAR\Sunday\Extension\Router\RouterInterface;
use MyVendor\BeMart\Auth\HtmlAdminSessionAdapter;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Module\App;
use Ray\Di\AbstractModule;
use Throwable;

use function assert;
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
        if ($app->httpCache->isNotModified($cacheServer)) {
            $app->httpCache->transfer();

            return 0;
        }

        $request = $app->router->match($globals, $server);
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
