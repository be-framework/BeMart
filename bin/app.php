<?php

declare(strict_types=1);

/**
 * CLI entry point — env-gated context selection.
 *
 * Reads APP_CONTEXT to pick which Module class drives the injector. The
 * convention mirrors BEAR\Package\Module — context 'foo' resolves to
 * MyVendor\BeMart\Module\FooModule (PascalCase + 'Module' suffix). Falls
 * back to 'app' (AppModule + dev-default bindings) when APP_CONTEXT is
 * unset or empty.
 *
 * Slice 5 deliberately keeps this thin — no built-in router, no AOP
 * compile step. The point is to prove that the env switch correctly
 * picks ProdModule (which structurally suppresses PII log writes — see
 * Phase B Slice 3 in HANDOVER.md) and not just AppModule. Heavier
 * BEAR\Package\Injector wiring (Compiler, AppInterface) is deferred
 * until an HTTP server is actually stood up.
 *
 * Usage:
 *   php bin/app.php <uri> [json-body]
 *     - uri      : page://self/... or app://self/...
 *     - json-body: optional POST/PUT body as JSON
 *
 * Examples:
 *   php bin/app.php 'page://self/'
 *   APP_CONTEXT=prod php bin/app.php 'page://self/shopping/checkout' \
 *     '{"preOrderId":"aaaa00000000000000000000000000000000aaaa"}'
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
    fwrite(STDERR, sprintf('Unknown APP_CONTEXT="%s" — class %s not found.%s', $context, $moduleClass, PHP_EOL));
    exit(2);
}

$meta = new Meta('MyVendor\\BeMart', $context, $appDir);
$injector = new Injector(new $moduleClass($meta), $meta->tmpDir);
$resource = $injector->getInstance(ResourceInterface::class);

$uri = $argv[1] ?? null;
if ($uri === null) {
    fwrite(STDERR, 'Usage: php bin/app.php <uri> [json-body]' . PHP_EOL);
    exit(2);
}

$body = [];
if (isset($argv[2])) {
    /** @var mixed $decoded */
    $decoded = json_decode($argv[2], true);
    if (! is_array($decoded)) {
        fwrite(STDERR, 'Body must be a JSON object.' . PHP_EOL);
        exit(2);
    }

    /** @var array<string, mixed> $body */
    $body = $decoded;
}

$ro = $body === []
    ? $resource->get($uri)
    : $resource->post($uri, $body);

echo json_encode([
    'context' => $context,
    'uri' => $uri,
    'code' => $ro->code,
    'body' => $ro->body,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;

exit($ro->code >= 400 ? 1 : 0);
