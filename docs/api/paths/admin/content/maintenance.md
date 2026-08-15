<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/content/maintenance
EC-CUBE メンテナンス管理 — admin CMS page.

PORT-side note: EC-CUBE's `MaintenanceController` toggles the
maintenance-mode marker file; there is no long-lived business entity for
it. This resource models the admin affordance as an explicit
`doToggleMaintenance` transition and persists the operational marker
through {@see \MaintenanceModeInterface}. `body['isMaintenance']` drives
which 有効/無効 button the template shows.




## GET
ALPS `goMaintenance` に対応する GET 操作。

**ALPS**: `goMaintenance` - メンテナンス管理を見る



### Request

_No parameters required_

### Response

[Object: GET /admin/content/maintenance response](../schemas/get-admin-content-maintenance.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| isMaintenance | boolean|null | メンテナンス中フラグ - /admin/content/maintenance の処理状態を示すメンテナンス中フラグ。画面表示や冪等処理結果の分岐に使う真偽値。 | Required |  |  |
| csrfToken | string|null | CSRFトークン - /admin/content/maintenance のHTMLフォーム送信用CSRFトークン。 | Optional | {"minLength":0,"maxLength":160} |  |

#### Links

| Relation | URL |
|----------|-----|
| doToggleMaintenance | [<code>page://self/admin/content/maintenance</code>](/admin/content/maintenance.md) |
## PUT
Toggles maintenance mode to an explicit state (doToggleMaintenance).

ALPS marks it `idempotent` → PUT.

**ALPS**: `doToggleMaintenance` - メンテナンス状態を切り替える



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| enabled | bool | 処理状態フラグ（入力） - 観察値 'true', 'false'。 |  | Required | {"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | true |
| mode | string | フォーム送信モード |  | Optional | {"minLength":0,"maxLength":32,"$comment":"HTML form submit marker; Resource workflow calls omit it."} |  |


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