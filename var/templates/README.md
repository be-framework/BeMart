# BeMart storefront templates — EC-CUBE template port

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

## Per-page workflow for the remaining ~138 pages

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
