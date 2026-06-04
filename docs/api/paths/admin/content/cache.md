<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/content/cache
EC-CUBE キャッシュ管理 — admin CMS thin renderer (Phase 3 HTML).

PORT-side note: EC-CUBE's `CacheController` clears the Twig / Symfony
cache directories on POST; there is no Be domain entity for it. The
`Content/cache.twig` screen is a single "キャッシュ削除" button — the
only `form_widget` call is the CSRF `_token` (EC-CUBE-runtime, kept as
a render-diff residual). This resource is therefore a THIN HTML
RENDERER only — it carries no `be/src/` Becoming chain and no form,
authenticating at the resource layer via {@see \AdminSession}.

FLAGGED: the cache-clear POST action is not modelled (it is an
infra/operational action, not a domain mutation); only the GET render
is provided.




## GET


### Request

_No parameters required_

### Response

_Not available_