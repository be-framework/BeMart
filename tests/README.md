# tests/ — BeMart test framework

BeMart adopts the **BEAR.Skeleton 3-tier test structure**: the same
workflow assertions are written once and exercised at two transport
levels — in-process resource objects and a real HTTP request/response
cycle.

## Layout

```
tests/
├── Resource/      resource-unit tests (page://*, app://*)
├── Auth/          session-adapter tests
├── Module/        Ray.Di wiring tests
├── Router/        Aura.Router-backed RouteTable tests
├── EntryPoint/    bin/app.php CLI entry-point tests
├── Hypermedia/    in-process workflow tests
│   ├── WorkflowTest.php      storefront purchase-spine workflow (base class)
│   └── RoutedResource.php    ResourceInterface over the Aura RouteTable
├── Http/          real-HTTP workflow tests
│   ├── WorkflowTest.php      extends Hypermedia\WorkflowTest, swaps the transport
│   ├── HttpResource.php      ResourceInterface over a koriym/php-server + curl
│   ├── index.php             server entry — sets APP_CONTEXT=html, requires public/index.php
│   └── log/                  per-run request/response log (git-ignored)
└── Support/       shared test exceptions
```

`be/tests/` holds the Be-domain layer tests and runs in the `resource`
suite.

## The three suites

`phpunit.xml` defines three test suites:

| Suite | Directories | What it proves |
|---|---|---|
| `resource` | `tests/Resource`, `tests/Auth`, `tests/Module`, `tests/Router`, `tests/EntryPoint`, `be/tests` | units behave in isolation |
| `hypermedia` | `tests/Hypermedia` | a full user workflow holds **in-process** |
| `http` | `tests/Http` | the same workflow holds over a **real HTTP / cookie boundary** |

## Write once, run at two transports

`tests/Http/WorkflowTest` **extends** `tests/Hypermedia/WorkflowTest` and
overrides only `setUp()` — it swaps `$this->resource` for an
`HttpResource`. Every workflow assertion in the base class therefore runs
again, unchanged, over real HTTP. A new workflow added to the hypermedia
base is automatically covered at the HTTP tier too.

The two tiers are not redundant. The `hypermedia` tier runs the whole
workflow in one process against one injector — its DI singletons live
for the entire test. The `http` tier issues each request to a real
`php -S` server, where the front controller rebuilds the injector per
request and only the session cookie is carried between calls — exactly
as in production. Bugs where state lives in a request-scoped singleton
instead of the session — e.g. an in-memory cart — are invisible to the
`hypermedia` tier and caught only by the `http` tier.

## Running

The suites that need no database run with `DATABASE_URL` emptied:

```bash
DATABASE_URL='' vendor/bin/phpunit --no-coverage                       # all suites
DATABASE_URL='' vendor/bin/phpunit --no-coverage --testsuite hypermedia
DATABASE_URL='' vendor/bin/phpunit --no-coverage --testsuite http
DATABASE_URL='' vendor/bin/phpunit --no-coverage --testsuite hypermedia,http
```

With `DATABASE_URL` empty, 3 prod-DB context tests fail by design
(`tests/EntryPoint`, `tests/Module`); they need a live MariaDB.

### Prerequisite for the `http` suite

The `http` suite starts a `php -S` server through `koriym/php-server`
and issues requests with `curl`; both the PHP binary and `curl` must be
on `PATH`. No separate `php-cgi` binary is needed.

`HttpResource` delegates the server lifecycle to `koriym/php-server` —
the maintained component BEAR's own test tooling uses. The one
BeMart-specific addition is a per-instance curl cookie jar, so the PHP
session survives across the workflow; the skeleton's stock `HttpResource`
targets stateless JSON APIs and omits cookie handling.

## Known provisional aspects

These work today but are candidates for future consolidation:

- **`RoutedResource` is a shim.** The hypermedia tier follows links
  through the Aura-backed `RouteTable`, not BEAR's native `#[Link]` / `crawl`
  hypermedia.
- **`canonicalizeFormFields` maps field names.** The workflow test
  translates HTML wire field names (`_token`, `product_id`) into resource
  argument names (`csrfToken`, `productCode`). The form and the resource
  should eventually agree on names so this mapping is unnecessary.
- **Coverage is one workflow.** Only the storefront purchase spine is
  covered so far; the structure is in place for more.
