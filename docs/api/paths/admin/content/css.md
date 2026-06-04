<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/content/css
EC-CUBE カスタマイズCSS編集 — admin CMS thin renderer (Phase 3 HTML).

PORT-side note: EC-CUBE's `CssController` reads / writes a single
`customize.css` file on disk; there is no Be domain entity for it (the
customize-CSS file was not modelled in any ALPS wave). This resource is
therefore a THIN HTML RENDERER only — it carries no `be/src/` Becoming
chain. It authenticates at the resource layer via
{@see \AdminSession} (the same guard the Be CMS Finals apply)
and exposes an empty {@see \AdminCssForm} for the
`Content/css.twig` port to render via `{{ form.input('css') }}`.

FLAGGED: a future `be/src/` wave should model the customize-CSS file as
a Be domain (Get/Update Inputs + Final) so this resource can carry the
real persisted CSS instead of an empty editor.




## GET


### Request

_No parameters required_

### Response

_Not available_
## PUT
Saves the customize CSS (doUpdateContentCss). ALPS idempotent → PUT.



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| css | string |  |  | Optional |  |  |


### Response

_Not available_