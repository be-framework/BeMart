<?php

declare(strict_types=1);

namespace MyVendor\BeMart;

use BEAR\Resource\Code;
use BEAR\Resource\Exception\BadRequestException;
use BEAR\Resource\Method;
use BEAR\Resource\ResourceObject;
use BEAR\Sunday\Extension\Application\AppInterface;
use MyVendor\BeMart\Module\App;
use Throwable;

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
use function str_starts_with;

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

        try {
            $app = Injector::getInstance($context)->getInstance(AppInterface::class);
            assert($app instanceof App);
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

        [$routingGlobals, $routingServer] = $this->routingInput($request, $server);
        $route = $app->router->match($routingGlobals, $routingServer);
        if (Method::tryFrom($route->method) === null) {
            return $this->fail($isCli, 405, 'Method Not Allowed', $context);
        }

        ob_start();
        try {
            $ro = $app->resource->{$route->method}->uri($route->path)($route->query);
            assert($ro instanceof ResourceObject);
        } catch (BadRequestException $e) {
            ob_end_clean();

            return $this->fail(
                $isCli,
                $this->exceptionStatusCode($e),
                $e->getMessage() !== '' ? $e->getMessage() : 'Bad Request',
                $context,
            );
        } catch (Throwable $e) {
            ob_end_clean();
            if ($isCli) {
                fwrite(STDERR, $e->getMessage() . PHP_EOL);

                return 1;
            }

            throw $e;
        }

        $isRedirect = isset($ro->headers['Location']);
        $isDownload = ! $isRedirect && $this->isDownloadResponse($ro->headers, $isHtml);
        $view = $isHtml && ! $isRedirect && ! $isDownload ? $ro->toString() : null;
        ob_end_clean();

        if ($isCli) {
            if ($isHtml) {
                echo (string) $view;

                return $ro->code >= 400 ? 1 : 0;
            }

            echo json_encode([
                'context' => $context,
                'method' => $route->method,
                'path' => $request->target,
                'uri' => $route->path,
                'code' => $ro->code,
                'body' => $ro->body,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . PHP_EOL;

            return $ro->code >= 400 ? 1 : 0;
        }

        $statusCode = $this->httpStatusCode($ro->code, $isHtml, $isRedirect);
        http_response_code($statusCode);
        if ($isHtml) {
            foreach ($ro->headers as $name => $value) {
                if (is_string($value)) {
                    $this->emitHeader($name, $value, $statusCode);
                }
            }

            if (! isset($ro->headers['Content-Type'])) {
                header('Content-Type: text/html; charset=utf-8');
            }

            echo $isDownload ? $this->downloadBody($ro->body) : (string) $view;

            return $ro->code >= 400 ? 1 : 0;
        }

        if ($isDownload) {
            foreach ($ro->headers as $name => $value) {
                if (is_string($value)) {
                    $this->emitHeader($name, $value, $statusCode);
                }
            }

            echo $this->downloadBody($ro->body);

            return $ro->code >= 400 ? 1 : 0;
        }

        $view = $ro->toString();
        foreach ($ro->headers as $name => $value) {
            if (is_string($value)) {
                $this->emitHeader($name, $value, $statusCode);
            }
        }

        if (! isset($ro->headers['Content-Type'])) {
            header('Content-Type: application/json; charset=utf-8');
        }

        echo $view;

        return $ro->code >= 400 ? 1 : 0;
    }

    private function emitHeader(string $name, string $value, int $statusCode): void
    {
        if (strtolower($name) === 'location') {
            header($name . ': ' . $value, true, $statusCode);

            return;
        }

        header($name . ': ' . $value);
    }

    private function httpStatusCode(int $resourceCode, bool $isHtml, bool $isRedirect): int
    {
        if ($isHtml && $isRedirect && ($resourceCode < 300 || $resourceCode >= 400)) {
            return Code::SEE_OTHER;
        }

        return $resourceCode;
    }

    /**
     * @param array<string, mixed> $server
     * @return array{
     *     0: array{_GET: array<string, mixed>, _POST: array<string, mixed>},
     *     1: array{REQUEST_URI: string, REQUEST_METHOD: string, CONTENT_TYPE?: string, HTTP_CONTENT_TYPE?: string, HTTP_RAW_POST_DATA?: string}
     * }
     */
    private function routingInput(BootstrapRequest $request, array $server): array
    {
        $method = strtolower($request->method);
        $routingServer = [
            'REQUEST_METHOD' => $method,
            'REQUEST_URI' => $request->target,
        ];
        foreach (['CONTENT_TYPE', 'HTTP_CONTENT_TYPE', 'HTTP_RAW_POST_DATA'] as $key) {
            if (isset($server[$key]) && is_string($server[$key])) {
                $routingServer[$key] = $server[$key];
            }
        }

        $globals = ['_GET' => [], '_POST' => []];
        if ($method === 'get' || $method === 'head') {
            $globals['_GET'] = $request->params;
        } else {
            $globals['_POST'] = $request->params;
        }

        return [$globals, $routingServer];
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
        return $this->normalizeContext($context, $defaultContext);
    }

    /**
     * @param non-empty-string $context
     * @param non-empty-string $defaultContext
     *
     * @return non-empty-string
     */
    private function normalizeContext(string $context, string $defaultContext): string
    {
        $normalized = match ($context) {
            'app' => 'hal-api-app',
            'fake' => 'fake-hal-api-app',
            'dev' => 'dev-fake-hal-api-app',
            'test' => 'test-hal-api-app',
            'html' => 'html-hal-app',
            'html-test' => 'html-test-hal-api-app',
            'prod' => 'prod-hal-api-app',
            'html-prod' => 'html-prod-hal-api-app',
            default => $context,
        };

        if ($normalized === $context || ! str_starts_with($defaultContext, 'cli-')) {
            return $normalized;
        }

        /** @var non-empty-string */
        return 'cli-' . $normalized;
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

    /** @param array<string, mixed> $headers */
    private function isDownloadResponse(array $headers, bool $isHtml): bool
    {
        $contentType = $headers['Content-Type'] ?? null;
        if (! is_string($contentType)) {
            return false;
        }

        if (str_contains($contentType, 'application/pdf') || str_contains($contentType, 'application/zip')) {
            return true;
        }

        return $isHtml && ! str_contains($contentType, 'text/html');
    }

    /** @param mixed $body */
    private function downloadBody(mixed $body): string
    {
        if (! is_array($body)) {
            return (string) $body;
        }

        foreach (['csv', 'pdf'] as $key) {
            $value = $body[$key] ?? null;
            if (is_string($value)) {
                return $value;
            }
        }

        return json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    private function exceptionStatusCode(BadRequestException $e): int
    {
        $code = $e->getCode();

        return $code >= 400 && $code < 600 ? $code : Code::BAD_REQUEST;
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
