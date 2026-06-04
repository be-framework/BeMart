<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/content/maintenance
EC-CUBE メンテナンス管理 — admin CMS thin renderer (Phase 3 HTML).

PORT-side note: EC-CUBE's `MaintenanceController` toggles the
maintenance-mode marker file; there is no Be domain entity for it. The
`Content/maintenance.twig` screen is a single有効/無効 toggle button —
the only `form_widget` call is the CSRF `_token` (EC-CUBE-runtime,
kept as a render-diff residual). This resource is a THIN HTML RENDERER
only — it carries no `be/src/` Becoming chain, authenticating at the
resource layer via {@see \AdminSession}. `body['isMaintenance']`
drives which toggle button the template shows; it defaults to false
(maintenance off — the fresh-install state).

FLAGGED: the maintenance-toggle POST action and the persisted
maintenance state are not modelled (operational, not a domain
mutation); only the GET render of the off-state is provided.




## GET


### Request

_No parameters required_

### Response

_Not available_