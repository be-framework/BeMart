<?php

declare(strict_types=1);

namespace MyVendor\BeMart;

use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Router\RouteMethodNotAllowedException;
use MyVendor\BeMart\Router\RouteNotFoundException;
use MyVendor\BeMart\Router\RouteTable;
use MyVendor\BeMart\Router\Router;
use Throwable;

use function array_key_exists;
use function assert;
use function count;
use function file_get_contents;
use function fwrite;
use function getenv;
use function header;
use function headers_sent;
use function http_response_code;
use function is_array;
use function is_string;
use function json_decode;
use function json_encode;
use function ob_end_clean;
use function ob_start;
use function parse_str;
use function parse_url;
use function putenv;
use function session_start;
use function session_status;
use function sprintf;
use function strtolower;
use function str_contains;

use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;
use const PHP_EOL;
use const PHP_SAPI;
use const PHP_SESSION_ACTIVE;
use const PHP_URL_PATH;
use const PHP_URL_QUERY;
use const STDERR;

final class Bootstrap
{
    public function __construct(private readonly bool $loggable = false)
    {
    }

    /**
     * @param array<string, mixed> $globals
     * @param array<string, mixed> $server
     *
     * @param non-empty-string $defaultContext
     */
    public function __invoke(string $defaultContext, array $globals, array $server): int
    {
        $context = $this->context($defaultContext);
        putenv('APP_CONTEXT=' . $context);
        $_SERVER['APP_CONTEXT'] = $context;
        if ($this->loggable) {
            $_SERVER['BEMART_BOOTSTRAP_LOGGABLE'] = '1';
        }

        $isCli = PHP_SAPI === 'cli';
        $isHtml = str_contains($context, 'html');

        if ($isHtml && ! $isCli && session_status() !== PHP_SESSION_ACTIVE && ! headers_sent()) {
            session_start([
                'use_strict_mode' => true,
                'cookie_httponly' => true,
                'cookie_samesite' => 'Lax',
            ]);
        }

        try {
            $request = $isCli
                ? $this->cliRequest($globals)
                : $this->httpRequest($server);
        } catch (InvalidCliRequestException $e) {
            fwrite(STDERR, $e->getMessage() . PHP_EOL);

            return 2;
        }

        $router = new Router(RouteTable::default());
        try {
            $matched = $router->match($request->method, $request->path);
        } catch (RouteNotFoundException) {
            return $this->fail($isCli, 404, 'Not Found', $context);
        } catch (RouteMethodNotAllowedException) {
            return $this->fail($isCli, 405, 'Method Not Allowed', $context);
        }

        $params = $matched->params + $request->params;
        $this->normalizeWireAliases($params);

        try {
            $resource = Injector::getInstance($context)->getInstance(ResourceInterface::class);
            assert($resource instanceof ResourceInterface);
        } catch (AppContextModuleNotFoundException $e) {
            if ($isCli) {
                fwrite(STDERR, sprintf('Unknown APP_CONTEXT="%s".%s', $context, PHP_EOL));

                return 2;
            }

            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Unknown APP_CONTEXT', 'context' => $context]);

            return 1;
        }

        ob_start();
        try {
            $ro = match ($request->method) {
                'get' => $resource->get($matched->resource, $params),
                'post' => $resource->post($matched->resource, $params),
                'put' => $resource->put($matched->resource, $params),
                'delete' => $resource->delete($matched->resource, $params),
                default => null,
            };
        } catch (Throwable $e) {
            ob_end_clean();
            if ($isCli) {
                fwrite(STDERR, $e->getMessage() . PHP_EOL);

                return 1;
            }

            throw $e;
        }

        if ($ro === null) {
            ob_end_clean();

            return $this->fail($isCli, 405, 'Method Not Allowed', $context);
        }

        $isRedirect = isset($ro->headers['Location']);
        $view = $isHtml && ! $isRedirect ? $ro->toString() : null;
        ob_end_clean();

        if ($isCli) {
            if ($isHtml) {
                echo (string) $view;

                return $ro->code >= 400 ? 1 : 0;
            }

            echo json_encode([
                'context' => $context,
                'method' => $request->method,
                'path' => $request->target,
                'uri' => $matched->resource,
                'code' => $ro->code,
                'body' => $ro->body,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . PHP_EOL;

            return $ro->code >= 400 ? 1 : 0;
        }

        http_response_code($ro->code);
        if ($isHtml) {
            foreach ($ro->headers as $name => $value) {
                if (is_string($value)) {
                    header($name . ': ' . $value);
                }
            }

            if (! isset($ro->headers['Content-Type'])) {
                header('Content-Type: text/html; charset=utf-8');
            }

            echo (string) $view;

            return $ro->code >= 400 ? 1 : 0;
        }

        header('Content-Type: application/json; charset=utf-8');
        foreach ($ro->headers as $name => $value) {
            if (is_string($value)) {
                header($name . ': ' . $value);
            }
        }

        echo json_encode($ro->body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return $ro->code >= 400 ? 1 : 0;
    }

    /**
     * @param non-empty-string $defaultContext
     *
     * @return non-empty-string
     */
    private function context(string $defaultContext): string
    {
        $context = getenv('APP_CONTEXT');
        if ($context === false || $context === '') {
            return $defaultContext;
        }

        /** @var non-empty-string $context */
        return $context;
    }

    /** @param array<string, mixed> $globals */
    private function cliRequest(array $globals): BootstrapRequest
    {
        /** @var list<string> $argv */
        $argv = isset($globals['argv']) && is_array($globals['argv']) ? $globals['argv'] : [];
        if (count($argv) < 3) {
            throw new InvalidCliRequestException('Usage: php bin/*.php <method> <path-with-query>');
        }

        $method = strtolower((string) $argv[1]);
        $target = (string) $argv[2];

        return $this->requestFromTarget($method, $target, []);
    }

    /** @param array<string, mixed> $server */
    private function httpRequest(array $server): BootstrapRequest
    {
        $method = strtolower((string) ($server['REQUEST_METHOD'] ?? 'get'));
        $target = (string) ($server['REQUEST_URI'] ?? '/');
        $body = [];
        if ($method !== 'get') {
            $raw = file_get_contents('php://input');
            if (is_string($raw) && $raw !== '') {
                /** @var mixed $decoded */
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    /** @var array<string, mixed> $decoded */
                    $body = $decoded;
                }
            }

            /** @psalm-suppress MixedAssignment */
            foreach ($_POST as $key => $value) {
                if (is_string($key)) {
                    $body[$key] = $value;
                }
            }
        }

        return $this->requestFromTarget($method, $target, $body);
    }

    /** @param array<string, mixed> $body */
    private function requestFromTarget(string $method, string $target, array $body): BootstrapRequest
    {
        $path = (string) (parse_url($target, PHP_URL_PATH) ?? '/');
        $queryString = (string) (parse_url($target, PHP_URL_QUERY) ?? '');
        $query = [];
        if ($queryString !== '') {
            parse_str($queryString, $query);
        }

        /** @var array<string, mixed> $query */
        $params = $body + $query;

        return new BootstrapRequest($method, $target, $path, $params);
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
        ];
        foreach ($wireAliases as $wire => $canonical) {
            if (array_key_exists($wire, $params) && ! array_key_exists($canonical, $params)) {
                $params[$canonical] = $params[$wire];
                unset($params[$wire]);
            }
        }
    }

    private function fail(bool $isCli, int $status, string $message, string $context): int
    {
        if ($isCli) {
            echo json_encode([
                'context' => $context,
                'code' => $status,
                'error' => $message,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . PHP_EOL;

            return 1;
        }

        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => $message]);

        return 1;
    }
}
