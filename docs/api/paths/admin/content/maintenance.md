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
ALPS `goMaintenance` に対応する GET 操作。

**ALPS**: `goMaintenance`



### Request

_No parameters required_

### Response

[Object: GET /admin/content/maintenance response](../schemas/get-admin-content-maintenance.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| isMaintenance | boolean|null | メンテナンス中フラグ - /admin/content/maintenance の処理状態を示すメンテナンス中フラグ。画面表示や冪等処理結果の分岐に使う真偽値。 | Required |  |  |

#### Links

| Relation | URL |
|----------|-----|
| doToggleMaintenance | [<code>page://self/admin/content/maintenance</code>](/admin/content/maintenance.md) |
## PUT
Toggles maintenance mode to an explicit state (doToggleMaintenance).

ALPS marks it `idempotent` → PUT.

**ALPS**: `doToggleMaintenance`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| enabled | bool | 処理状態フラグ（入力） - 観察値 'true', 'false'。 |  | Required | {"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | true |


### Response

[Object: PUT /admin/content/maintenance response](../schemas/put-admin-content-maintenance.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| message | string|null | 処理メッセージ - /admin/content/maintenance のレスポンスに含まれる処理結果メッセージ。注文時お問い合わせ欄ではなく、画面遷移や完了表示のための通知文。 | Optional | {"minLength":0,"maxLength":32} | 配送は平日希望です。 |
| transitionId | string | ALPS遷移ID - このレスポンス/操作が対応するALPS遷移ID。クライアントの状態遷移追跡に使う。 | Required | {"minLength":2,"maxLength":96,"pattern":"^(go|do)[A-Z][A-Za-z0-9]*$"} | doAddCartItem |
| isMaintenance | boolean|null | メンテナンス中フラグ - /admin/content/maintenance の処理状態を示すメンテナンス中フラグ。画面表示や冪等処理結果の分岐に使う真偽値。 | Required |  |  |

#### Links

| Relation | URL |
|----------|-----|
| goSystemInfo | [<code>page://self/admin/system</code>](/admin/system.md) |