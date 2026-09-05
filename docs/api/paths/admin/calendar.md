<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/calendar
EC-CUBE 定休日カレンダー設定 — Setting/Shop Tier-2.

Renderer and action surface for `Setting/Shop/calendar.twig`.




## GET
ALPS `goCalendar` に対応する GET 操作。

**ALPS**: `goCalendar`



### Request

_No parameters required_

### Response

[Object: GET /admin/calendar response](../schemas/get-admin-calendar.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| form | object|array|null | 入力フォーム - Aura/WebForm由来のフォームオブジェクト。フレームワーク内部構造のためschemaでは存在と型のみを契約する。 | Optional | {"$comment":"Aura/WebForm\u7531\u6765\u306e\u4e0d\u900f\u660e\u30d5\u30a9\u30fc\u30e0\u8868\u73fe\u3002Resource\u5883\u754c\u3067\u306f\u30d5\u30a9\u30fc\u30e0\u306e\u5b58\u5728\u3068\u30b3\u30f3\u30c6\u30ad\u30b9\u30c8\u3060\u3051\u3092\u5951\u7d04\u3057\u3001\u5185\u90e8\u69cb\u9020\u306f\u30d5\u30ec\u30fc\u30e0\u30ef\u30fc\u30af\u5883\u754c\u306b\u59d4\u306d\u308b\u305f\u3081\u8ffd\u52a0\u30ad\u30fc\u5236\u7d04\u3092\u7f6e\u304b\u306a\u3044\u3002"} |  |
| calendars | array|null | カレンダー一覧 - /admin/calendar のレスポンスで扱うカレンダー一覧。配列要素はALPS意味とFake観察に基づき、固定できない動的列は例外理由を台帳化する。 | Required | {"items":{"type":["object","null"],"title":"\u30ab\u30ec\u30f3\u30c0\u30fc\u884c","description":"/admin/calendar \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u30ab\u30ec\u30f3\u30c0\u30fc\u884c\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `calendars` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002","properties":{"form":{"type":["object","array","null"],"title":"\u5165\u529b\u30d5\u30a9\u30fc\u30e0","description":"Aura/WebForm\u7531\u6765\u306e\u30d5\u30a9\u30fc\u30e0\u30aa\u30d6\u30b8\u30a7\u30af\u30c8\u3002\u30d5\u30ec\u30fc\u30e0\u30ef\u30fc\u30af\u5185\u90e8\u69cb\u9020\u306e\u305f\u3081schema\u3067\u306f\u5b58\u5728\u3068\u578b\u306e\u307f\u3092\u5951\u7d04\u3059\u308b\u3002","$comment":"Aura/WebForm\u7531\u6765\u306e\u4e0d\u900f\u660e\u30d5\u30a9\u30fc\u30e0\u8868\u73fe\u3002Resource\u5883\u754c\u3067\u306f\u30d5\u30a9\u30fc\u30e0\u306e\u5b58\u5728\u3068\u30b3\u30f3\u30c6\u30ad\u30b9\u30c8\u3060\u3051\u3092\u5951\u7d04\u3057\u3001\u5185\u90e8\u69cb\u9020\u306f\u30d5\u30ec\u30fc\u30e0\u30ef\u30fc\u30af\u5883\u754c\u306b\u59d4\u306d\u308b\u305f\u3081\u8ffd\u52a0\u30ad\u30fc\u5236\u7d04\u3092\u7f6e\u304b\u306a\u3044\u3002"},"title":{"type":["string","null"],"minLength":0,"maxLength":255,"title":"\u51e6\u7406\u30bf\u30a4\u30c8\u30eb","description":"/admin/calendar \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u3067\u8868\u793a\u307e\u305f\u306f\u30c6\u30f3\u30d7\u30ec\u30fc\u30c8\u63cf\u753b\u306b\u4f7f\u3046\u51e6\u7406\u30bf\u30a4\u30c8\u30eb\u3002\u540c\u540dproperty\u3067\u3082\u89aa\u6587\u8108 `root` \u306b\u3088\u3063\u3066\u610f\u5473\u3092\u5206\u3051\u308b\u3002"},"id":{"type":["string","integer","null"],"title":"ID","description":"Fake\u89b3\u5bdf\u6587\u5b57\u9577 13\u301c32; \u89b3\u5bdf\u5024 'ad000000000000000000000000000001', 'ad000000000000000000000000000003', 'fedcba9876543210fedcba9876543210', '10000000aaaa1111bbbb2222cccc3333', 'ad000000000000000000000000000002', '0123456789abcdef0123456789abcdef', 'aaaaaaaa00000000bbbbbbbb11111111', '20000000dddd2222eeee3333ffff4444'\u3002","example":"ad000000000000000000000000000001","minLength":0,"maxLength":128,"$comment":"Fake\u6587\u5b57\u5217ID\u3068EC-CUBE\u6574\u6570ID\u306e\u4e21\u65b9\u304c\u89b3\u5bdf\u3055\u308c\u308b\u305f\u3081\u3001\u3053\u306e\u5883\u754c\u3060\u3051mixedBoundaryId\u3068\u3057\u3066\u6271\u3046\u3002"},"hasError":{"type":["boolean","null"],"title":"\u30a8\u30e9\u30fc\u6709\u7121","description":"/admin/calendar \u306e\u51e6\u7406\u72b6\u614b\u3092\u793a\u3059\u30a8\u30e9\u30fc\u6709\u7121\u3002\u753b\u9762\u8868\u793a\u3084\u51aa\u7b49\u51e6\u7406\u7d50\u679c\u306e\u5206\u5c90\u306b\u4f7f\u3046\u771f\u507d\u5024\u3002"},"holiday":{"type":["string","integer","null"],"maximum":2147483647,"title":"\u4f11\u65e5\u6307\u5b9a","description":"/admin/calendar \u306e\u51e6\u7406\u6587\u8108\u304b\u3089\u6d3e\u751f\u3057\u305f\u4f11\u65e5\u6307\u5b9a\u3002ALPS\u57fa\u790e\u8a9e\u3060\u3051\u3067\u306f\u5358\u4f4d\u3084\u7528\u9014\u304c\u4e0d\u8db3\u3059\u308b\u305f\u3081\u3001\u3053\u306eResource\u4e0a\u306e\u610f\u5473\u3092\u660e\u793a\u3059\u308b\u3002","minLength":0,"maxLength":64,"$comment":"EC-CUBE\u4e92\u63db\u306e\u30d5\u30a9\u30fc\u30e0/\u4e00\u89a7\u5883\u754c\u3067\u6570\u5024\u3068\u6587\u5b57\u5217\u306e\u4e21\u65b9\u304c\u89b3\u5bdf\u3055\u308c\u308b\u3002\u696d\u52d9\u89e3\u91c8\u306fResource/Semantic\u5c64\u3067\u884c\u3046\u3002"}},"additionalProperties":false,"$comment":"\u914d\u5217\u8981\u7d20\u306fFake/Resource\u3067\u89b3\u5bdf\u3055\u308c\u305f\u65e2\u77e5property\u306b\u56fa\u5b9a\u3059\u308b\u3002\u65b0\u3057\u3044\u5217\u304c\u5fc5\u8981\u306b\u306a\u3063\u305f\u5834\u5408\u306fSemantic-Ex\u89b3\u5bdf\u306b\u8ffd\u52a0\u3057\u3066schema\u3092\u66f4\u65b0\u3059\u308b\u3002"},"minItems":0} |  |
| errors | array|null | 検証エラー - /admin/calendar のレスポンスで扱う検証エラー。ALPS、Fake観察、Resource境界の形状から導いた業務契約。 | Optional | {"items":{"type":"string","title":"\u30a8\u30e9\u30fc\u30e1\u30c3\u30bb\u30fc\u30b8","minLength":0,"maxLength":1000,"description":"/admin/calendar \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u51e6\u7406\u884c\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `errors` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002"}} |  |
| csrfToken | string |  | Optional | {"$ref":"#/$defs/csrfToken"} |  |

#### Links

| Relation | URL |
|----------|-----|
| doCreateCalendarHoliday | [<code>page://self/admin/calendar</code>](/admin/calendar.md) |
| doUpdateCalendar | [<code>page://self/admin/calendar</code>](/admin/calendar.md) |
| doDeleteCalendarHoliday | [<code>page://self/admin/calendar</code>](/admin/calendar.md) |
## POST
EC-CUBE doUpdateCalendar / doCreateCalendarHoliday.

**ALPS**: `doUpdateCalendar`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| operation | string | 操作種別（入力） - /admin/calendar のunsafe操作結果を表す操作種別。成功時の差分、処理件数、冪等状態をクライアントに返す。 | update | Optional | {"minLength":0,"maxLength":64,"default":"update","$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} |  |
| title | string | 処理タイトル（入力） - /admin/calendar のレスポンスで表示またはテンプレート描画に使う処理タイトル。同名propertyでも親文脈 `root` によって意味を分ける。 |  | Optional | {"minLength":0,"maxLength":255,"default":"","$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} |  |
| holiday | string | 休日指定（入力） - /admin/calendar の処理文脈から派生した休日指定。ALPS基礎語だけでは単位や用途が不足するため、このResource上の意味を明示する。 |  | Optional | {"default":"","minLength":0,"maxLength":64,"$comment":"EC-CUBE\u4e92\u63db\u306e\u30d5\u30a9\u30fc\u30e0/\u4e00\u89a7\u5883\u754c\u3067\u6570\u5024\u3068\u6587\u5b57\u5217\u306e\u4e21\u65b9\u304c\u89b3\u5bdf\u3055\u308c\u308b\u3002\u696d\u52d9\u89e3\u91c8\u306fResource/Semantic\u5c64\u3067\u884c\u3046\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} |  |
| calendarId |  | カレンダーID（入力） - /admin/calendar のレスポンスで対象を識別するカレンダーID。DB採番ID、Fake文字列ID、互換境界IDのどれに該当するかをschemaの型とコメントで分ける。 |  | Optional | {"minLength":0,"maxLength":128,"$comment":"\u30ab\u30ec\u30f3\u30c0\u30fcID\uff08\u5165\u529b\uff09\u306f\u696d\u52d9\u4e0aID\u3060\u304c\u3001HTTP\u30d5\u30a9\u30fc\u30e0\u3067\u306f\u6587\u5b57\u5217\u3068\u3057\u3066\u5c4a\u304f\u3002Resource/Semantic\u5c64\u306e\u691c\u8a3c\u3092\u901a\u3059\u305f\u3081transport schema\u3067\u306fstring|integer\u3092\u8a31\u5bb9\u3059\u308b\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} |  |


### Response

[Object: POST /admin/calendar response](../schemas/post-admin-calendar.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| title | string|null | 処理タイトル - /admin/calendar のレスポンスで表示またはテンプレート描画に使う処理タイトル。同名propertyでも親文脈 `root` によって意味を分ける。 | Required | {"minLength":0,"maxLength":255} |  |
| message | string|null | 処理メッセージ - /admin/calendar のレスポンスに含まれる処理結果メッセージ。注文時お問い合わせ欄ではなく、画面遷移や完了表示のための通知文。 | Optional | {"minLength":0,"maxLength":32} | 配送は平日希望です。 |
| transitionId | string | ALPS遷移ID - このレスポンス/操作が対応するALPS遷移ID。クライアントの状態遷移追跡に使う。 | Required | {"minLength":2,"maxLength":96,"pattern":"^(go|do)[A-Z][A-Za-z0-9]*$"} | doAddCartItem |
| calendarId | string|int|null | カレンダーID - /admin/calendar のレスポンスで対象を識別するカレンダーID。DB採番ID、Fake文字列ID、互換境界IDのどれに該当するかをschemaの型とコメントで分ける。 | Required | {"minLength":0,"maxLength":128,"$comment":"Fake\u6587\u5b57\u5217ID\u3068EC-CUBE\u6574\u6570ID\u306e\u4e21\u65b9\u304c\u89b3\u5bdf\u3055\u308c\u308b\u305f\u3081\u3001\u3053\u306e\u5883\u754c\u3060\u3051mixedBoundaryId\u3068\u3057\u3066\u6271\u3046\u3002"} |  |
| holiday | string|int|null | 休日指定 - /admin/calendar の処理文脈から派生した休日指定。ALPS基礎語だけでは単位や用途が不足するため、このResource上の意味を明示する。 | Required | {"maximum":2147483647,"minLength":0,"maxLength":64,"$comment":"EC-CUBE\u4e92\u63db\u306e\u30d5\u30a9\u30fc\u30e0/\u4e00\u89a7\u5883\u754c\u3067\u6570\u5024\u3068\u6587\u5b57\u5217\u306e\u4e21\u65b9\u304c\u89b3\u5bdf\u3055\u308c\u308b\u3002\u696d\u52d9\u89e3\u91c8\u306fResource/Semantic\u5c64\u3067\u884c\u3046\u3002"} |  |

#### Links

| Relation | URL |
|----------|-----|
| goCalendar | [<code>page://self/admin/calendar</code>](/admin/calendar.md) |
## DELETE
EC-CUBE doDeleteCalendarHoliday.

**ALPS**: `doDeleteCalendarHoliday`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| calendarId |  | カレンダーID（入力） - /admin/calendar のレスポンスで対象を識別するカレンダーID。DB採番ID、Fake文字列ID、互換境界IDのどれに該当するかをschemaの型とコメントで分ける。 |  | Optional | {"minLength":0,"maxLength":128,"$comment":"\u30ab\u30ec\u30f3\u30c0\u30fcID\uff08\u5165\u529b\uff09\u306f\u696d\u52d9\u4e0aID\u3060\u304c\u3001HTTP\u30d5\u30a9\u30fc\u30e0\u3067\u306f\u6587\u5b57\u5217\u3068\u3057\u3066\u5c4a\u304f\u3002Resource/Semantic\u5c64\u306e\u691c\u8a3c\u3092\u901a\u3059\u305f\u3081transport schema\u3067\u306fstring|integer\u3092\u8a31\u5bb9\u3059\u308b\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} |  |


### Response

[Object: DELETE /admin/calendar response](../schemas/delete-admin-calendar.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| message | string|null | 処理メッセージ - /admin/calendar のレスポンスに含まれる処理結果メッセージ。注文時お問い合わせ欄ではなく、画面遷移や完了表示のための通知文。 | Optional | {"minLength":0,"maxLength":32} | 配送は平日希望です。 |
| transitionId | string | ALPS遷移ID - このレスポンス/操作が対応するALPS遷移ID。クライアントの状態遷移追跡に使う。 | Required | {"minLength":2,"maxLength":96,"pattern":"^(go|do)[A-Z][A-Za-z0-9]*$"} | doAddCartItem |
| calendarId | string|int|null | カレンダーID - /admin/calendar のレスポンスで対象を識別するカレンダーID。DB採番ID、Fake文字列ID、互換境界IDのどれに該当するかをschemaの型とコメントで分ける。 | Required | {"minLength":0,"maxLength":128,"$comment":"Fake\u6587\u5b57\u5217ID\u3068EC-CUBE\u6574\u6570ID\u306e\u4e21\u65b9\u304c\u89b3\u5bdf\u3055\u308c\u308b\u305f\u3081\u3001\u3053\u306e\u5883\u754c\u3060\u3051mixedBoundaryId\u3068\u3057\u3066\u6271\u3046\u3002"} |  |

#### Links

| Relation | URL |
|----------|-----|
| goCalendar | [<code>page://self/admin/calendar</code>](/admin/calendar.md) |