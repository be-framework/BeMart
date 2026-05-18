<?php

declare(strict_types=1);

/**
 * HTTP front controller — env-gated context selection.
 *
 * Mirrors bin/app.php for HTTP. Reads APP_CONTEXT to choose Module:
 *   APP_CONTEXT=prod  → ProdModule (no PII log file write)
 *   APP_CONTEXT=app   → AppModule  (dev default, DevBecoming + DevSemanticLogger)
 *   (unset / empty)   → app
 *
 * The dispatch is intentionally minimal — REQUEST_METHOD + REQUEST_URI →
 * resource call, JSON response. No router, no AOP compile, no caching
 * headers. Slice 5 is about wiring the env gate, not building a
 * production HTTP server. A proper front controller (BEAR WebRouter +
 * compiled injector + APP_CONTEXT=prod-hal-app composition) lands in a
 * later slice.
 */

use BEAR\AppMeta\Meta;
use BEAR\Resource\ResourceInterface;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

require dirname(__DIR__) . '/vendor/autoload.php';

$appDir = dirname(__DIR__);
$context = getenv('APP_CONTEXT');
if ($context === false || $context === '') {
    $context = 'app';
}

$moduleClass = 'MyVendor\\BeMart\\Module\\' . ucfirst($context) . 'Module';
if (! class_exists($moduleClass) || ! is_subclass_of($moduleClass, AbstractModule::class)) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Unknown APP_CONTEXT', 'context' => $context]);
    exit;
}

$meta = new Meta('MyVendor\\BeMart', $context, $appDir);
$injector = new Injector(new $moduleClass($meta), $meta->tmpDir);
$resource = $injector->getInstance(ResourceInterface::class);

$method = strtolower((string) ($_SERVER['REQUEST_METHOD'] ?? 'get'));
$requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
$path = (string) (parse_url($requestUri, PHP_URL_PATH) ?? '/');
$queryString = (string) (parse_url($requestUri, PHP_URL_QUERY) ?? '');

$query = [];
if ($queryString !== '') {
    parse_str($queryString, $query);
}

$body = $query;
if ($method !== 'get') {
    $raw = file_get_contents('php://input');
    if (is_string($raw) && $raw !== '') {
        /** @var mixed $decoded */
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $body = $decoded + $body;
        }
    }

    /** @psalm-suppress MixedArgumentTypeCoercion */
    $body = $_POST + $body;
}

$uri = 'page://self' . rtrim($path, '/');
if ($uri === 'page://self') {
    $uri = 'page://self/';
}

$ro = match ($method) {
    'get' => $resource->get($uri, $body),
    'post' => $resource->post($uri, $body),
    'put' => $resource->put($uri, $body),
    'delete' => $resource->delete($uri, $body),
    default => null,
};

if ($ro === null) {
    http_response_code(405);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Method Not Allowed', 'method' => $method]);
    exit;
}

http_response_code($ro->code);
header('Content-Type: application/json; charset=utf-8');
foreach ($ro->headers as $name => $value) {
    if (is_string($value)) {
        header($name . ': ' . $value);
    }
}

echo json_encode($ro->body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
