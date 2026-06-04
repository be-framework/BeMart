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
├── Router/        Aura.Router route-map tests
├── EntryPoint/    bin/app.php CLI entry-point tests
├── Hypermedia/    in-process workflow tests
│   ├── FlowCustomerInquiryTest.php  semantic inquiry workflow
│   ├── WorkflowTest.php      storefront purchase-spine workflow
│   └── RoutedResource.php    ResourceInterface over Aura.Router
├── Http/          real-HTTP workflow tests
│   ├── FlowCustomerInquiryTest.php  extends Hypermedia\FlowCustomerInquiryTest
│   ├── WorkflowTest.php      extends Hypermedia\WorkflowTest, swaps the transport
│   ├── HttpResource.php      ResourceInterface over a koriym/php-server + curl
│   ├── index.php             server entry — sets APP_CONTEXT=html, requires public/index.php
│   └── log/                  per-run request/response log (git-ignored)
└── Support/       shared workflow base and test exceptions
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

## Workflow postconditions

A workflow test is not just "no exception while clicking through".
It should prove the semantic postcondition of the flow whenever that
postcondition is visible through public affordances.

Default patterns:

- CRUD: create, read back, update, read back, delete, then read none.
- Registration: register, complete, then behave as the registered
  customer through sign-in, signed-in Top, or MyPage.
- Publish/edit flows: write in the admin surface, then read the result
  from the customer-facing or management surface.
- Notification/send flows: the workflow closes through complete,
  receipt/ticket evidence, and a public closure link; mail body, storage,
  and hidden side effects are asserted in Be / Resource / SQL contract
  tests. `flow-customer-inquiry` intentionally shows this shape with a
  public `ticketId` because there is no inquiry body readback resource.

Do not add DB reads to a workflow test only to prove persistence. If the
saved state is not observable through a public resource, put that proof
in the command/storage/SQL contract layer and keep the workflow focused
on the hypermedia journey.

## Write once, run at two transports

Workflow tests extend `tests/Support/Hypermedia/AbstractWorkflowTest`.
The PHP projection implements `newResource()` with an in-process
`ResourceInterface`; the HTTP projection extends the same workflow class
and swaps only `newResource()` for `HttpResource`. Every workflow
assertion in the base class therefore runs again, unchanged, over real
HTTP.

`tests/Http/WorkflowTest` is the older storefront HTML spine and still
overrides `setUp()` directly because it uses a routed HTML adapter. New
semantic workflows should prefer the `newResource()` swap pattern used by
`FlowCustomerInquiryTest`.

The two tiers are not redundant. The `hypermedia` tier runs the whole
workflow in one process against one injector — its DI singletons live
for the entire test. The `http` tier issues each request to a real
`php -S` server, where the front controller rebuilds the injector per
request and only the session cookie is carried between calls — exactly
as in production. Bugs where state lives in a request-scoped singleton
instead of the session — e.g. an in-memory cart — are invisible to the
`hypermedia` tier and caught only by the `http` tier.

## HAL follow contract

`follow()` is a GET navigation DSL. It follows a rel from the current
resource response and asserts the next response is `200 OK`.

For the in-process tier, `follow()` delegates to `ResourceInterface::href()`
and BEAR.Resource resolves the rel declared by `#[Link]`. For the HTTP tier,
`HttpResource::href()` reads the rendered HAL representation and follows
`_links.<rel>.href` with GET. HAL links do not carry an HTTP method.

Unsafe or idempotent action transitions such as `do*` therefore do not use
`follow()`. The workflow step calls `post()`, `put()`, `patch()`, or
`delete()` directly with the request payload it knows from the ALPS/profile
contract.

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
  through Aura.Router, not BEAR's native `#[Link]` / `crawl`
  hypermedia.
- **`canonicalizeFormFields` maps field names.** The workflow test
  translates HTML wire field names (`_token`, `product_id`) into resource
  argument names (`csrfToken`, `productCode`). The form and the resource
  should eventually agree on names so this mapping is unnecessary.
- **Coverage is one workflow.** Only the storefront purchase spine is
  covered so far; the structure is in place for more.
