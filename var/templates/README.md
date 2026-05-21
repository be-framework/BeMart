# BeMart templates — EC-CUBE template port

Phase 3 renders the BEAR.Sunday resources as HTML. The templates here are
**ports of EC-CUBE 4.3's `default`-theme Twig templates**, not freshly
authored markup.

## Why a port, not fresh markup

ALPS (`alps.json`) deliberately contains **no presentation** — only
information structure (data vocabulary + state transitions). Verifying
rendered HTML against ALPS would be grading presentation against a spec
that is silent on presentation.

The honest reference for the presentation layer is therefore **EC-CUBE's
own `default`-theme templates**. A BeMart template must be a port of the
corresponding EC-CUBE template: same markup structure, same `ec-*` CSS
classes, same layout. Byte-identical is not the goal, but the rendered
HTML must match EC-CUBE's **as closely as honestly achievable**, and every
residual difference must be explainable.

## Port method (per page)

For each page, port the EC-CUBE template
(`tools/ec-cube-source/src/Eccube/Resource/template/default/<Page>/...twig`)
into `var/templates/<resource-path>.html.twig`:

1. **Copy the markup verbatim.** Keep every tag, every `ec-*` class, the
   block structure, the loop/branch skeleton.
2. **Rebind EC-CUBE-isms** — only the dynamic bindings change:
   - `{{ 'key'|trans }}` → the Japanese literal the key resolves to in
     `tools/ec-cube-source/src/Eccube/Resource/locale/messages.ja.yaml`.
     BeMart has no translation layer; substituting the literal is the
     lower-noise choice (no Twig extension to register, output is stable
     and diffable). `%placeholder%` interpolation is done inline in Twig.
   - `asset('x')` → `/x` passthrough (`BeMartTwigExtension`). BeMart has
     no asset-hash pipeline; the path keeps the `<link>/<script>` markup.
   - `url(route, params)` / `path(route, params)` → deterministic
     `/{route}?{query}` (`BeMartTwigExtension`). No Symfony router.
   - `price` filter → JPY `NumberFormatter` (`BeMartTwigExtension`),
     identical to EC-CUBE's `EccubeExtension::getPriceFilter` (`￥1,200`).
   - `is_granted`, `csrf_token`, `app.session.flashbag`, configurable
     `Layout.*` block regions, plugin snippets → EC-CUBE-runtime only.
     Keep the surrounding markup; the runtime-only node becomes an
     enumerated residual.
3. **Rebind the data loops** to the resource body (`$ro->body`) keys.
4. **Omit, never invent.** Where EC-CUBE's template uses a field the
   resource body does not carry (e.g. product images, ProductClass joins
   dropped in the 厳密移植 Grade-C scope), omit it and record it as an
   explained residual in the page's render test. Do not fabricate data.

The shared layout `base.html.twig` is the port of EC-CUBE's
`default_frame.twig`; every storefront page `{% extends 'base.html.twig' %}`.

## Verification — residual diff against EC-CUBE's output

Each page has a render test (`tests/Resource/<Page>HtmlRenderTest.php`)
that proves fidelity, not just "data appears":

1. Render EC-CUBE's **real** template (from the gitignored 4.3 clone)
   through a standalone Twig env with EC-CUBE's Twig API stubbed
   (`trans`→ja literal, `is_granted`→false, `asset/url/path`→deterministic,
   `price`→JPY, frame block-includes→empty). See `EcCubeStub`,
   `EcCubeStubLoader`, `EcCubeFlashBag`.
2. Render BeMart's ported template via the `html` context.
3. Feed both the **same logical data**.
4. Whitespace-collapse both to line lists and diff.
5. Assert every differing line is in an **enumerated residual allowlist**
   whose comments justify each entry. If BeMart's markup structurally
   diverges beyond the allowlist, the test fails.

The allowlist is the **honesty metric**: it is the exhaustive, reviewed
list of what could not be matched and why.

### Cart residual allowlist (the Step 1 pilot)

The Cart port's residual diff (`CartHtmlRenderTest`) is ~16 lines:

- **frame, CSRF**: the `<meta eccube-csrf-token>` and the inline
  `$.ajaxSetup` jQuery script — EC-CUBE per-request CSRF runtime, no
  BeMart equivalent.
- **frame, title/meta**: `<title>` is `<shop_name> / <page>`; only the
  shop name differs (`BeMart` vs `EC-CUBE`). `meta.twig` SEO tags — no
  `Page` entity.
- **cart row, product**: product thumbnail `<img>` + `ec-cartRow__img`
  wrapper, the product-detail `<a>` link, and `ClassCategory` lines —
  `CartItemEntity` carries only `productCode/quantity/price` (no
  ProductClass/Product join in scope). BeMart renders the bare
  `productCode` as the row name.
- **cart row, operation links**: the delete/up/down `<a>` links — EC-CUBE
  keys by `productClassId`, BeMart by `productCode`; same anchors, same
  classes, only the id param differs. EC-CUBE also adds
  `csrf_token_for_anchor()` — omitted (no CSRF widget).
- **delivery-fee-free**: the `ec-cartRole__progress` "あと N 円で送料無料"
  message — depends on `BaseInfo` thresholds the body does not carry.

## Form pages — the Ray.WebFormModule recipe

Most EC-CUBE storefront pages are read-only data pages (the recipe
above). A subset are **form pages** — they render `<input>`s and accept a
POST (Login, Entry, Contact, Forgot, ...). EC-CUBE renders form inputs
through the Symfony FormView (`form_widget(form.login_email)`); BeMart's
wave-1 ports authored static `<input>`s with no value/error binding,
which left the inputs unverifiable against EC-CUBE and inflated the
render-diff residual.

The form-page recipe adopts **`ray/web-form-module`** — the
BEAR.Sunday-idiomatic form library (Aura.Input + Aura.Filter + Aura.Html
+ Ray.Di). The Login page (`src/Resource/Page/Login.php` +
`src/Form/LoginForm.php` + `var/templates/Page/Login.html.twig` +
`tests/Resource/LoginHtmlRenderTest.php`) is the pilot; the remaining
form pages follow it.

### Design principle — validation authority stays with Be Framework

BeMart already validates in the domain via Be Framework Semantics (the
ALPS-derived rules) and the Final/exception layer. **Do NOT duplicate
business rules into Aura.Filter** — that would drift from the spec.
Ray.WebFormModule's role is strictly: **form definition + HTML rendering
+ repopulation (re-show submitted values) + (optionally) CSRF**.
Validation authority remains the Be Framework Becoming chain.

Consequently the `#[FormValidation]` aspect is **NOT used**: that aspect
makes Aura.Filter own the verdict and call an `onFailure` method, which
conflicts with the Becoming flow. The `AbstractForm` is used purely as a
**field-definition + renderer**: the resource instantiates it, binds
submitted values, and populates errors from the Be result. The form's
Aura.Filter may carry minimal *non-authoritative* structural checks
(required/blank) for a future fast-UX pre-check, but the resource never
consults the filter verdict.

### 1. Define an `AbstractForm` (`src/Form/<Name>Form.php`)

Subclass `Ray\WebFormModule\AbstractForm`. In `init()` declare the
fields with the **EC-CUBE field names / ids / attributes** (ported from
EC-CUBE's `Form/Type/...Type` + the template's `form_widget` `attr`
options) so the rendered `<input>` markup carries EC-CUBE's `ec-*`
shape:

```php
public function init(): void
{
    $this->setField('login_email', 'text')->setAttribs([
        'id' => 'login_email', 'style' => 'ime-mode: disabled;',
        'placeholder' => 'メールアドレス', 'autofocus' => 'autofocus',
    ]);
    // non-authoritative structural check only — authority is the Be domain
    $this->filter->validate('login_email')->isNotBlank();
}
```

Add a `fillValues(array)` passthrough to `fill()` (repopulation) and a
`setDomainError(field, message)` that records bridged errors; override
`error()` so bridged Be-domain errors take precedence over Aura.Filter
messages. See `LoginForm` for the canonical shape.

### 2. Wire it into the `Page` resource

The resource constructor-injects `Ray\WebFormModule\FormFactory` (bound
once in `AppModule` — it is self-sufficient, needs no other bindings).
`onGet` builds an empty form; `onPost` on a domain rejection builds a
form with the submitted values + the bridged error. The form is exposed
as `body['form']`:

```php
$this->body['form'] = $this->formFactory->newInstance(LoginForm::class);
```

JSON contexts (`app`, `prod`, `test`) ignore `body['form']`; the
JSON-context tests assert key-wise on `body` and are unaffected.

### 3. Bridge Be-domain errors to form errors

When the Becoming chain rejects input — a `SemanticVariableException`
(malformed shape) or a domain failure exception (e.g.
`LoginFailedException`) — the resource catches it, repopulates the form
(`fillValues`) and attaches the domain message (`setDomainError`). The
Becoming chain reached the verdict; the form only transports it. Note
the user-enumeration guard: the repopulated email lives **inside**
`body['form']`, never as a top-level `body['email']` key, so the JSON
body stays enumeration-safe while the HTML page re-shows the value.

### 4. Render in Twig

Install `WebFormModule` in `HtmlModule` (HTML context only — keeps the
JSON contexts and their tests untouched). The port renders inputs with
`{{ form.input('field')|raw }}` and inline field errors with
`{{ form.error('field') }}`. Form-LEVEL messages (EC-CUBE's single
`ec-errorMessage` block) render from `body.message`.

### 5. Build the render-diff test

Same residual-diff standard as the data pages, with one move specific to
form pages: stub EC-CUBE's `form_widget(form.<field>)` calls to render
through the **same `AbstractForm`** (returning a `Twig\Markup` so it is
not double-escaped). Because the inputs are now produced by a real form
object exercised identically on both sides, the two `<input>`s diff to
ZERO and the form-widget residual family is eliminated — the residual
shrinks to the genuinely EC-CUBE-runtime-only frame material. This is
honest, not circular: `AbstractForm::init()` is itself a port of
EC-CUBE's form type, so it IS the agreed reference for the widgets.

For Login this dropped the render-diff residual from wave-1's 15 lines
(8 of which were the unverified form-widget family) to **11** — all
shared `<head>` / inline-script frame residual, none form-related.

## Admin pages — the admin-theme port recipe

Everything above describes the **storefront** port (~40 pages, all done).
EC-CUBE also has an **admin** UI — 77 page templates under
`tools/ec-cube-source/src/Eccube/Resource/template/admin/`. **Admin
Tier-1 (34 pages) is done** — see "Fan-out status" below; the recipe in
this section is the established, exercised one. Admin pages are ported
with the SAME two recipes (data-page + form-page) and the SAME
residual-diff verification standard; only two things differ.

### Difference 1 — a different layout

Storefront pages `{% extends 'base.html.twig' %}` (the port of EC-CUBE's
default-theme `default_frame.twig`). **Admin pages
`{% extends 'admin-base.html.twig' %}`** — `var/templates/admin-base.html.twig`
is the port of EC-CUBE's *admin-theme* `default_frame.twig`
(`template/admin/default_frame.twig`). The admin frame is structurally
different: a left sidebar nav (`c-mainNavArea`), a top header bar
(`c-headerBar`) with the logged-in operator's menu, and a
`c-contentsArea` content region with a `c-pageTitle`. The admin pages
set `title` / `sub_title` / `route` and fill `{% block main %}` /
`{% block stylesheet %}` / `{% block javascript %}`.

`admin-base.html.twig` keeps EC-CUBE's admin markup skeleton + `c-*`
classes verbatim. The EC-CUBE-ism rebinding is the same as the
storefront frame (`trans` → ja literal, `asset`/`url` →
`BeMartTwigExtension`, the CSRF `<meta>` + `$.ajaxSetup` script →
EC-CUBE-runtime-only residual), plus two admin-specific ones:

- **operator header menu** (`app.user.*` — login date, name,
  change-password / 2FA / logout links) → BeMart's html context has no
  operator entity, so the `c-headerBar__userMenu` anchor shows a fixed
  `管理者 様` label. Enumerated as residual.
- **dynamic sidebar nav** (`@admin/nav.twig` loops `eccubeNav`, the menu
  tree, with `active_menus()` state) → BeMart has no nav tree, so the
  nav is inlined minimally: only the static ホーム / 情報 bookend `<li>`s
  (EC-CUBE's `nav.twig` `{% for %}` over `eccubeNav` is dropped).
  Enumerated as residual.

The other admin frame includes (`@admin/alert.twig`, `info.twig`,
`notice_debug_mode.twig`, `snippet.twig`, `pager.twig`,
`search_items.twig`, `@common/lang.twig`) are flash / notice / plugin /
pager / saved-search-chips / JS-i18n fragments with no BeMart equivalent
— dropped, exactly as the storefront frame drops `meta.twig` /
`block.twig`. `EcCubeAdminStubLoader::STUBBED_EMPTY` serves them empty so
they contribute nothing to the render diff on either side.

### Difference 2 — admin auth context

Admin resources (`src/Resource/Page/Admin/...`) are authenticated at the
resource layer via `AdminSessionInterface`. `AppModule` binds the
**anonymous** `FakeAdminSession(null)` by default, so an admin page in
the `html` context returns `403 FORBIDDEN` unless the test rebinds
`AdminSessionInterface` to a seeded admin id (the render tests
`override()` `HtmlModule` with `new FakeAdminSession('ad00…01')` — the
same move the admin *resource* tests already make).

### Otherwise the recipes transfer unchanged

- **Data/list pages** — port the EC-CUBE admin template, rebind the
  `{% for %}` loop to the resource body, render-diff against EC-CUBE's
  real admin template via `EcCubeAdminStubLoader` (the admin counterpart
  of `EcCubeStubLoader` — it serves `@admin/default_frame.twig` +
  `@admin/nav.twig` for real, stubs the rest empty). Pilot:
  `Page/Admin/News/NewsList.html.twig` + `AdminNewsListHtmlRenderTest`
  (residual ~15 lines — the EC-CUBE-runtime `<head>` baseline + the
  admin operator-menu / dynamic-nav families + the omitted
  `News.visible` display-status column + the `csrf_token_for_anchor()`
  on the delete link).
- **Form/CRUD pages** — `ray/web-form-module`; `<Name>Form extends
  AbstractForm` ports EC-CUBE's `Form/Type/Admin/...Type`, the resource
  exposes `body['form']`, Twig renders `{{ form.input('x')|raw }}`, the
  render-diff test stubs EC-CUBE's `form_widget` to delegate to the same
  form object so the inputs diff to zero. Pilot:
  `src/Form/AdminNewsForm.php` + `Page/Admin/News/News.html.twig` +
  `AdminNewsHtmlRenderTest` (residual ~30 lines — the admin-frame
  baseline + the form `_token` hidden CSRF input + the omitted `visible`
  select). A section's admin `trans` keys live in a **per-section
  ja-messages file** — see the next subsection.

### The per-section ja-message mechanism — parallel-wave safety

A render-diff test substitutes EC-CUBE's `{{ 'key'|trans }}` calls with
the Japanese literal the key resolves to in EC-CUBE's
`messages.ja.yaml`. The storefront ports keep that map in one shared
method, `EcCubeStub::jaMessages()`. If every admin section-wave appended
its keys there, that single method would be a **merge-conflict hotspot**
— two waves editing the same lines block parallel work.

The admin `trans` keys are therefore split **per section**, under
`tests/Resource/Admin/`:

- **`EcCubeStub::jaMessages()`** stays the FROZEN storefront baseline.
  Admin section-waves NEVER touch it.
- **`Admin/AdminJaMessages.php`** is the shared admin infra. Its
  `chrome()` carries the keys EVERY admin page needs — the admin frame
  (`default_frame.twig`) + sidebar (`nav.twig`) chrome + `admin.common.*`
  action labels. Stable; edited only when the frame port itself changes.
  Its `forSection(array $sectionKeys)` builds the full map for one
  section's test: `jaMessages()` (storefront baseline) + `chrome()`
  (admin chrome) + the section's own keys.
- **`Admin/<Section>JaMessages.php`** — each section ships its OWN keys
  in its own file: a class with a `public static function keys(): array`
  returning `array<string,string>` copied verbatim from
  `messages.ja.yaml`. `ContentJaMessages` (News pilot) and
  `CustomerJaMessages` (Customer wave) are the worked examples.

A section's render test then does:

```php
use MyVendor\BeMart\Tests\Resource\Admin\AdminJaMessages;
use MyVendor\BeMart\Tests\Resource\Admin\CustomerJaMessages;

$messages = AdminJaMessages::forSection(CustomerJaMessages::keys());
$trans = static fn (string $k, array $p = []): string => /* substitute */ ;
```

and feeds `$trans` to its `trans` Twig filter/function stub.

**Why this makes the fan-out conflict-free:** adding a section-wave =
adding exactly ONE new file (`Admin/<Section>JaMessages.php`) + that
section's templates + tests. No wave touches `EcCubeStub.php`,
`AdminJaMessages.php` or `EcCubeAdminStubLoader.php` — the three shared
files. The remaining waves (Product / Order / Content / Setting/Shop /
Setting/System / Store / Top-level) can run in parallel with zero
cross-wave file contention.

### Fan-out status — 8 section-waves, Tier-1 done

The admin templates are organised by section directory under
`template/admin/`; each section ran as an independent wave (clean
file-path split, no cross-wave coupling — the per-section ja-message
mechanism above is what keeps them parallel-safe). Each wave ported its
**Tier-1** pages — list/data pages and simple CRUD whose BEAR resource
already serves a GET — and deferred **Tier-2**: multi-panel editors and
pages whose resource is action-only (POST/CSV/PDF) with no GET-serving
`onGet`.

| Section | Directory | Tier-1 done | Tier-2 deferred |
|---|---|---|---|
| Top-level | `admin/` | login, dashboard, change-password, 2FA verify/setup, empty placeholder — 6 | — |
| Product | `admin/Product/` | list, tag, class-name, class-category — 4 | `product` (~932L editor), `product_class` (~448L matrix), `category`, 4× `csv_*` |
| Order | `admin/Order/` | order list — 1 | `edit` (~1057L), `shipping` (~709L), `mail`, `mail_confirm`, `order_pdf`, `csv_shipping` |
| Customer | `admin/Customer/` | list, edit — 2 · **Tier-2:** delivery-edit — 1 | — (Customer complete) |
| Content | `admin/Content/` | news list/edit, page list/edit, layout list/edit, block list/edit, file, css, js, cache, maintenance — 13 | (essentially complete) |
| Setting/Shop | `admin/Setting/shop/` | payment list, delivery list, tax-rule list — 3 | `payment_edit`, `delivery_edit`, `order_status`, `csv`, `mail`, `tradelaw`, `shop_master`, `calendar` |
| Setting/System | `admin/Setting/system/` | member list, member edit, login-history — 3 · **Tier-2 wave:** authority, system, log, masterdata, security, two_factor_auth_edit — 6 | — (Setting/System complete) |
| Store/Plugin | `admin/Store/` | plugin list, template list — 2 | `plugin_install/search/confirm/confirm_uninstall/handler`, `authentication_setting`, `template_add` |

**Tier-1 total: 34 of 77 admin page templates** — plus the
**flow-manage-system Tier-2 wave** (6 pages: authority, system, log,
masterdata, security, two_factor_auth_edit) and the **Customer
delivery-edit Tier-2 page** (1 page) → **41 of 77 ported.** Each
Tier-1 wave followed the recipe page-for-page with no module/wiring
change beyond its own `Admin/<Section>JaMessages.php` and `<Name>Form`
classes; the four shared files (`admin-base.html.twig`,
`EcCubeAdminStubLoader`, `EcCubeStub`, `AdminJaMessages`) stayed
untouched — that is what kept the waves parallel-safe.

**Tier-2 (~36 pages remaining) is a different kind of work.** It is not
template porting — it needs new BEAR resources, `onGet` additions to
action-only resources, and `be/src` domain body-shape work. Plan it as a
resource-creation effort, section by section, NOT as another
template-port fan-out. The flow-manage-system Tier-2 wave is the worked
example: 5 new GET resources + `AuthorityRole::onGet()` + 3 `<Name>Form`
classes + `AdminMasterRegistry` body-shape methods, each with a
`*ResourceTest` and a `*HtmlRenderTest`. The Customer delivery-edit page
is the smallest worked example: 1 new GET resource + 1 `<Name>Form`,
completing its section. Per-section deferred lists are the table above;
`docs/phases/admin-fanout-plan.md` carries the full per-page audit.

## Per-page workflow (storefront data pages)

Each page is mechanical and self-contained:

1. Read EC-CUBE's template, port it (above), save to `var/templates/`.
2. Add `tests/Resource/<Page>HtmlRenderTest.php` (reuse `EcCubeStub` /
   `EcCubeStubLoader`); build the residual allowlist by running the diff
   and justifying each line.
3. No module/wiring changes — `HtmlModule` + `BeMartTwigExtension` are
   shared. Extend `BeMartTwigExtension` only if a page needs an
   EC-CUBE Twig helper not yet stubbed.

**Note — Twig compile cache.** TwigModule compiles templates into
`var/tmp/<context>/twig` with `auto_reload` off, so a changed template is
**not** picked up until that dir is cleared. Clear it
(`rm -rf var/tmp/html/twig`) after editing any `.html.twig`.
