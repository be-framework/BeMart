<?php

declare(strict_types=1);

/**
 * HTTP front controller — env-gated context selection + route dispatch.
 *
 * Mirrors bin/app.php for HTTP. Reads APP_CONTEXT to choose Module:
 *   APP_CONTEXT=prod  → ProdModule (no PII log file write)
 *   APP_CONTEXT=app   → AppModule  (dev default, DevBecoming + DevSemanticLogger)
 *   APP_CONTEXT=html  → HtmlModule (Twig HTML rendering — Phase 3 Step 1)
 *   (unset / empty)   → app
 *
 * Response representation is selected by context, not by Accept header:
 *   - JSON contexts (`app`, `prod`) emit `json_encode($ro->body)` with
 *     `application/json`. The resource bodies are PHP arrays.
 *   - The `html` context binds a Twig `RenderInterface` (see HtmlModule);
 *     here we call `$ro->toString()` so BEAR runs the bound renderer and
 *     emit `$ro->view` (rendered HTML) with the renderer-set Content-Type.
 *
 * ## Routing (Phase B Slice 9)
 *
 * This replaces the original minimal dispatch — which mapped REQUEST_URI
 * verbatim onto `page://self{path}`, so a template-emitted EC-CUBE URL
 * (`/products/detail/5`, `/help_tradelaw`) reached no resource and fell
 * through to an uncaught `Unbound` (HTTP 200 + Xdebug stack trace).
 *
 * Now {@see \MyVendor\BeMart\Router\Router} walks {@see RouteTable} — the
 * map shared with the `url()` / `path()` Twig helpers — and resolves an
 * HTTP `(method, path)` to a BEAR resource URI plus extracted path params.
 * The params are keyed by the resource's own `on{Method}` parameter names
 * (the table renames EC-CUBE's `id` → `productCode` etc.), then merged
 * into the request body so a path-segment id reaches the resource exactly
 * as a query param would.
 *
 * Failure semantics keep BEAR's `Code`:
 *   - unknown route          → 404 (RouteNotFoundException)
 *   - known route, bad verb  → 405 (RouteMethodNotAllowedException)
 *   - unsupported HTTP verb  → 405
 */

use BEAR\AppMeta\Meta;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Router\RouteMethodNotAllowedException;
use MyVendor\BeMart\Router\RouteNotFoundException;
use MyVendor\BeMart\Router\RouteTable;
use MyVendor\BeMart\Router\Router;
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

if ($context === 'html' && PHP_SAPI !== 'cli' && session_status() !== PHP_SESSION_ACTIVE && ! headers_sent()) {
    session_start([
        'use_strict_mode' => true,
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
    ]);
}

/**
 * Emit a JSON error and stop. The `html` context renders pages through
 * Twig, but a routing failure has no resource to render — a small JSON
 * body with the correct status code is the honest, deterministic answer.
 */
$fail = static function (int $status, string $message): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => $message]);
    exit;
};

$method = strtolower((string) ($_SERVER['REQUEST_METHOD'] ?? 'get'));
$requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
$path = (string) (parse_url($requestUri, PHP_URL_PATH) ?? '/');
$queryString = (string) (parse_url($requestUri, PHP_URL_QUERY) ?? '');

$query = [];
if ($queryString !== '') {
    parse_str($queryString, $query);
}

// Resolve the request through the shared route table BEFORE booting the
// injector — a 404/405 needs no resource graph.
$router = new Router(RouteTable::default());
try {
    $matched = $router->match($method, $path);
} catch (RouteNotFoundException) {
    $fail(404, 'Not Found');
} catch (RouteMethodNotAllowedException) {
    $fail(405, 'Method Not Allowed');
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

// Normalize EC-CUBE form-field names onto the resource parameter names.
// The ported templates keep EC-CUBE's wire conventions verbatim — the
// CSRF hidden input is `_token` (storefront, Symfony default) or
// `_csrf_token` (admin login), the admin login username field is
// `login_id`, and the customer login fields are `login_email` /
// `login_pass`. Product add-cart keeps EC-CUBE's hidden `product_id`
// while the Cart/Item resource accepts `productCode`. The resources
// expose the canonical camelCase params (`csrfToken`, `productCode`,
// `loginId`, `email`, `password`); this is the
// HTTP-boundary translation, the body-field counterpart to RouteTable's
// path-param renaming. An alias is applied only when the canonical key
// is absent, so an explicit JSON body still wins.
$wireAliases = [
    '_token' => 'csrfToken',
    '_csrf_token' => 'csrfToken',
    'product_id' => 'productCode',
    'login_id' => 'loginId',
    'login_email' => 'email',
    'login_pass' => 'password',
];
foreach ($wireAliases as $wire => $canonical) {
    if (array_key_exists($wire, $body) && ! array_key_exists($canonical, $body)) {
        /** @psalm-suppress MixedAssignment — request body is mixed by nature */
        $body[$canonical] = $body[$wire];
        unset($body[$wire]);
    }
}

// Path params win over query/body keys of the same name: the URL segment
// is the more specific source for that identifier.
$body = $matched->params + $body;

$meta = new Meta('MyVendor\\BeMart', $context, $appDir);
$injector = new Injector(new $moduleClass($meta), $meta->tmpDir);
$resource = $injector->getInstance(ResourceInterface::class);

$uri = $matched->resource;

// Buffer the resource call + render: a stray notice emitted mid-dispatch
// (e.g. a dependency-chain deprecation) would otherwise count as output
// and break the header() calls below ("headers already sent"). The buffer
// is discarded — only $ro->view / the JSON body is the real response.
ob_start();

$ro = match ($method) {
    'get' => $resource->get($uri, $body),
    'post' => $resource->post($uri, $body),
    'put' => $resource->put($uri, $body),
    'delete' => $resource->delete($uri, $body),
    default => $fail(405, 'Method Not Allowed'),
};

// A resource that set a `Location` header is a redirect (Post/Redirect/Get):
// the browser discards the body and follows the header, so there is nothing
// to render. Skipping `toString()` here is also what lets redirect-only
// resources — logout, which has no Twig template of its own — work in the
// html context instead of dying in Twig's template loader.
$isRedirect = isset($ro->headers['Location']);
$view = $context === 'html' && ! $isRedirect ? $ro->toString() : null;

ob_end_clean();

http_response_code($ro->code);

if ($context === 'html') {
    // Render through the context-bound RenderInterface (Twig). toString()
    // (called above, inside the buffer) ran the renderer, which also set
    // $ro->headers['Content-Type'].
    foreach ($ro->headers as $name => $value) {
        if (is_string($value)) {
            header($name . ': ' . $value);
        }
    }

    if (! isset($ro->headers['Content-Type'])) {
        header('Content-Type: text/html; charset=utf-8');
    }

    echo (string) $view;
    exit;
}

header('Content-Type: application/json; charset=utf-8');
foreach ($ro->headers as $name => $value) {
    if (is_string($value)) {
        header($name . ': ' . $value);
    }
}

echo json_encode($ro->body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
