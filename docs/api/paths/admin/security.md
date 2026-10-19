<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/security
EC-CUBE セキュリティ管理 — Setting/System Tier-2.

Hard ActionRedirect completion: `onGet` renders the current settings
read through the {@see \SecurityConfigWriterInterface} boundary, and
`onPut` drives the Be `doUpdateSecurity` transition
({@see \UpdateSecurityInput} → {@see \SecuritySettingsUpdated}) — the host
allow/deny lists and trusted-hosts pattern are written behind that
boundary (config/firewall side-effect isolated).




## GET
ALPS `doUpdateSecurity` に対応する GET 操作。

**ALPS**: `doUpdateSecurity`



### Request

_No parameters required_

### Response

[Object: GET /admin/security response](../schemas/get-admin-security.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| isSecureRequest | boolean|null | セキュアリクエスト判定 - /admin/security の処理状態を示すセキュアリクエスト判定。画面表示や冪等処理結果の分岐に使う真偽値。 | Required |  |  |
| form | object|array|null | 入力フォーム - Aura/WebForm由来のフォームオブジェクト。フレームワーク内部構造のためschemaでは存在と型のみを契約する。 | Optional | {"$comment":"Aura/WebForm\u7531\u6765\u306e\u4e0d\u900f\u660e\u30d5\u30a9\u30fc\u30e0\u8868\u73fe\u3002Resource\u5883\u754c\u3067\u306f\u30d5\u30a9\u30fc\u30e0\u306e\u5b58\u5728\u3068\u30b3\u30f3\u30c6\u30ad\u30b9\u30c8\u3060\u3051\u3092\u5951\u7d04\u3057\u3001\u5185\u90e8\u69cb\u9020\u306f\u30d5\u30ec\u30fc\u30e0\u30ef\u30fc\u30af\u5883\u754c\u306b\u59d4\u306d\u308b\u305f\u3081\u8ffd\u52a0\u30ad\u30fc\u5236\u7d04\u3092\u7f6e\u304b\u306a\u3044\u3002"} |  |

#### Links

| Relation | URL |
|----------|-----|
| doUpdateSecurity | [<code>page://self/admin/security</code>](/admin/security.md) |
## PUT
Updates the security settings (doUpdateSecurity). ALPS marks this
`idempotent` → PUT.

Failure mapping:
- Invalid CSRF                     → 403 (interceptor)
- SemanticVariableException        → 400
- UnauthorizedAdminAccessException → 403 (no admin session)

**ALPS**: `doUpdateSecurity`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| adminAllowHosts | string | 処理一覧（入力） - /admin/security のレスポンスで扱う処理一覧。ALPS、Fake観察、Resource境界の形状から導いた業務契約。 |  | Optional | {"minLength":0,"maxLength":255,"default":"","$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} |  |
| adminDenyHosts | string | 処理一覧（入力） - /admin/security のレスポンスで扱う処理一覧。ALPS、Fake観察、Resource境界の形状から導いた業務契約。 |  | Optional | {"minLength":0,"maxLength":255,"default":"","$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} |  |
| frontAllowHosts | string | 処理一覧（入力） - /admin/security のレスポンスで扱う処理一覧。ALPS、Fake観察、Resource境界の形状から導いた業務契約。 |  | Optional | {"minLength":0,"maxLength":255,"default":"","$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} |  |
| frontDenyHosts | string | 処理一覧（入力） - /admin/security のレスポンスで扱う処理一覧。ALPS、Fake観察、Resource境界の形状から導いた業務契約。 |  | Optional | {"minLength":0,"maxLength":255,"default":"","$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} |  |
| trustedHosts | string | 処理一覧（入力） - /admin/security のレスポンスで扱う処理一覧。ALPS、Fake観察、Resource境界の形状から導いた業務契約。 |  | Optional | {"minLength":0,"maxLength":255,"default":"","$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} |  |


### Response

[Object: PUT /admin/security response](../schemas/put-admin-security.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| message | string|null | 処理メッセージ - /admin/security のレスポンスに含まれる処理結果メッセージ。注文時お問い合わせ欄ではなく、画面遷移や完了表示のための通知文。 | Optional | {"minLength":0,"maxLength":32} | 配送は平日希望です。 |
| transitionId | string | ALPS遷移ID - このレスポンス/操作が対応するALPS遷移ID。クライアントの状態遷移追跡に使う。 | Required | {"minLength":2,"maxLength":96,"pattern":"^(go|do)[A-Z][A-Za-z0-9]*$"} | doAddCartItem |
| trustedHosts | string|null | 処理一覧 - /admin/security のレスポンスで扱う処理一覧。ALPS、Fake観察、Resource境界の形状から導いた業務契約。 | Optional | {"minLength":0,"maxLength":255} |  |

#### Links

| Relation | URL |
|----------|-----|
| goTwoFactorAuthSet | [<code>page://self/admin/two-factor-auth-set</code>](/admin/two-factor-auth-set.md) |