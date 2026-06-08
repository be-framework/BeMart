<?php

declare(strict_types=1);

namespace MyVendor\BeMart;

use BEAR\Resource\Method;
use BEAR\Resource\ResourceObject;
use BEAR\Sunday\Extension\Application\AppInterface;
use BEAR\Sunday\Extension\Transfer\TransferInterface;
use MyVendor\BeMart\Module\App;
use MyVendor\BeMart\Provide\Error\AppErrorPage;
use MyVendor\BeMart\Provide\Transfer\BeMartResponder;
use Throwable;

use function assert;
use function fwrite;
use function sprintf;
use function str_contains;

use const PHP_EOL;
use const PHP_SAPI;
use const STDERR;

final class Bootstrap
{
    public function __construct(
        private readonly bool $loggable = false,
        private readonly BootstrapContextResolver $contextResolver = new BootstrapContextResolver(),
        private readonly BootstrapRequestFactory $requestFactory = new BootstrapRequestFactory(),
        private readonly BeMartResponder $fallbackResponder = new BeMartResponder(),
    ) {
    }

    /**
     * @param array<string, mixed> $globals
     * @param array<string, mixed> $server
     *
     * @param non-empty-string $defaultContext
     */
    public function __invoke(string $defaultContext, array $globals, array $server): int
    {
        $context = $this->contextResolver->resolve($defaultContext);
        $this->contextResolver->publish($context, $this->loggable);

        $isCli = PHP_SAPI === 'cli';
        $isHtml = str_contains($context, 'html');
        $this->requestFactory->startHtmlSession($isHtml, $isCli);

        try {
            $request = $this->requestFactory->request($globals, $server, $isCli);
            $app = Injector::getInstance($context)->getInstance(AppInterface::class);
            assert($app instanceof App);
        } catch (InvalidCliRequestException $e) {
            fwrite(STDERR, $e->getMessage() . PHP_EOL);

            return 2;
        } catch (AppContextModuleNotFoundException) {
            if ($isCli) {
                fwrite(STDERR, sprintf('Unknown APP_CONTEXT="%s".%s', $context, PHP_EOL));

                return 2;
            }

            return $this->transferError($context, new BootstrapRequest('get', '/', '/', []), 500, 'Unknown APP_CONTEXT');
        }

        [$routingGlobals, $routingServer] = $this->requestFactory->routingInput($request, $server);
        $route = $app->router->match($routingGlobals, $routingServer);
        if (Method::tryFrom($route->method) === null) {
            return $this->transferError($context, $request, 405, 'Method Not Allowed', $route->method, $route->path, $app->responder);
        }

        $transferServer = BeMartResponder::withRouteContext($routingServer, $context, $request, $route->method, $route->path);
        try {
            $response = $app->resource->{$route->method}->uri($route->path)($route->query);
            assert($response instanceof ResourceObject);
            $response->transfer($app->responder, $transferServer);

            return $response->code >= 400 ? 1 : 0;
        } catch (Throwable $e) {
            $app->throwableHandler->handle($e, $route)->transfer();

            return 1;
        }
    }

    private function transferError(
        string $context, BootstrapRequest $request, int $status, string $message, string $method = '', string $path = '', TransferInterface|null $responder = null,
    ): int {
        $error = new AppErrorPage($status, ['message' => $message]);
        $server = BeMartResponder::withRouteContext([], $context, $request, $method, $path);
        $error->transfer($responder ?? $this->fallbackResponder, $server);

        return 1;
    }
}
