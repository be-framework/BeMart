{% raw %}
<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/content/js
EC-CUBE カスタマイズJavaScript編集 — admin CMS thin renderer
(Phase 3 HTML).

PORT-side note: EC-CUBE's `JsController` reads / writes a single
`customize.js` file on disk; there is no Be domain entity for it. This
resource is therefore a THIN HTML RENDERER only — it carries no
`be/src/` Becoming chain. It authenticates at the resource layer via
{@see \AdminSession} and exposes an empty {@see \AdminJsForm}
for the `Content/js.twig` port to render via `{{ form.input('js') }}`.

FLAGGED: a future `be/src/` wave should model the customize-JS file as
a Be domain so this resource can carry the real persisted JS.




## GET


### Request

_No parameters required_

### Response

_Not available_
## PUT
Saves the customize JS (doUpdateContentJs). ALPS idempotent → PUT.



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| js | string |  |  | Optional |  |  |


### Response

_Not available_
{% endraw %}
