<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/content/cache
EC-CUBE キャッシュ管理 — admin CMS page.

Hard ActionRedirect completion: `onPut` drives the Be `doClearCache`
transition ({@see \ClearCacheInput} → {@see \CacheCleared}); the actual
cache-directory purge is isolated behind
{@see \MyVendor\BeMart\Be\Reason\Service\CacheClearerInterface}. ALPS
marks the transition `idempotent` → PUT. `onGet` renders the screen.




## GET
ALPS `doClearCache` に対応する GET 操作。

**ALPS**: `doClearCache` - キャッシュを削除する



### Request

_No parameters required_

### Response

[Object: GET /admin/content/cache response](../schemas/get-admin-content-cache.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| csrfToken | string|null | CSRFトークン - /admin/content/cache のHTMLフォーム送信用CSRFトークン。 | Optional | {"minLength":0,"maxLength":160} |  |

#### Links

| Relation | URL |
|----------|-----|
| doClearCache | [<code>page://self/admin/content/cache</code>](/admin/content/cache.md) |
## PUT
Clears the application cache (doClearCache).

**ALPS**: `doClearCache` - キャッシュを削除する



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| mode | string | フォーム送信モード |  | Optional | {"minLength":0,"maxLength":32,"$comment":"HTML form submit marker; Resource workflow calls omit it."} |  |


### Response

[Object: PUT /admin/content/cache response](../schemas/put-admin-content-cache.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| message | string|null | 処理メッセージ - /admin/content/cache のレスポンスに含まれる処理結果メッセージ。注文時お問い合わせ欄ではなく、画面遷移や完了表示のための通知文。 | Optional | {"minLength":0,"maxLength":32} | 配送は平日希望です。 |
| transitionId | string | ALPS遷移ID - このレスポンス/操作が対応するALPS遷移ID。クライアントの状態遷移追跡に使う。 | Required | {"minLength":2,"maxLength":96,"pattern":"^(go|do)[A-Z][A-Za-z0-9]*$"} | doAddCartItem |

#### Links

| Relation | URL |
|----------|-----|
| goMaintenance | [<code>page://self/admin/content/maintenance</code>](/admin/content/maintenance.md) |