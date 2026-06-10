<?php

declare(strict_types=1);

namespace MyVendor\BeMart;

use BEAR\Resource\ResourceObject;
use BEAR\Sunday\Extension\Application\AppInterface;
use BEAR\Sunday\Extension\Router\RouterInterface;
use MyVendor\BeMart\Auth\HtmlAdminSessionAdapter;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Module\App;
use Throwable;

use function assert;

/**
 * @psalm-import-type Globals from RouterInterface
 * @psalm-import-type Server from RouterInterface
 */
final class Bootstrap
{
    /**
     * @param non-empty-string $context
     * @param Globals $globals
     * @param Server  $server
     *
     * @return 0|1
     */
    public function __invoke(string $context, array $globals, array $server): int
    {
        $app = Injector::getInstance($context)->getInstance(AppInterface::class);
        assert($app instanceof App);
        /** @var array{HTTP_IF_NONE_MATCH?: string} $cacheServer */
        $cacheServer = $server;
        if ($app->httpCache->isNotModified($cacheServer)) {
            $app->httpCache->transfer();

            return 0;
        }

        $request = $app->router->match($globals, $server);
        try {
            $adminSession = Injector::getInstance($context)->getInstance(AdminSession::class);
            if ($adminSession instanceof HtmlAdminSessionAdapter) {
                $adminSession->refresh();
            }

            $response = $app->resource->{$request->method}->uri($request->path)($request->query);
            assert($response instanceof ResourceObject);
            $server['_BEMART_CONTEXT'] = $context;
            $response->transfer($app->responder, $server);

            return 0;
        } catch (Throwable $e) {
            $app->throwableHandler->handle($e, $request)->transfer();

            return 1;
        }
    }
}
