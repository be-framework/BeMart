<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/master-data
EC-CUBE マスタデータ管理 — Setting/System Tier-2.

GET renderer backed by the existing Be admin-master registry. This is
body-shape work for the generic master-data page: the resource exposes
selectable master types plus rows as `{id, name}` without inventing
values in Twig.




## GET
ALPS `goMasterData` に対応する GET 操作。

**ALPS**: `goMasterData`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| masterType | string | マスタ種別（入力） - /admin/master-data の処理文脈から派生したマスタ種別。ALPS基礎語だけでは単位や用途が不足するため、このResource上の意味を明示する。 | tag | Optional | {"minLength":0,"maxLength":255,"default":"tag","$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} |  |


### Response

[Object: GET /admin/master-data response](../schemas/get-admin-master-data.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| form | object|array|null | 入力フォーム - Aura/WebForm由来のフォームオブジェクト。フレームワーク内部構造のためschemaでは存在と型のみを契約する。 | Optional | {"$comment":"Aura/WebForm\u7531\u6765\u306e\u4e0d\u900f\u660e\u30d5\u30a9\u30fc\u30e0\u8868\u73fe\u3002Resource\u5883\u754c\u3067\u306f\u30d5\u30a9\u30fc\u30e0\u306e\u5b58\u5728\u3068\u30b3\u30f3\u30c6\u30ad\u30b9\u30c8\u3060\u3051\u3092\u5951\u7d04\u3057\u3001\u5185\u90e8\u69cb\u9020\u306f\u30d5\u30ec\u30fc\u30e0\u30ef\u30fc\u30af\u5883\u754c\u306b\u59d4\u306d\u308b\u305f\u3081\u8ffd\u52a0\u30ad\u30fc\u5236\u7d04\u3092\u7f6e\u304b\u306a\u3044\u3002"} |  |
| masterTypes | array|null | マスタ種別一覧 - /admin/master-data のレスポンスで扱うマスタ種別一覧。配列要素はALPS意味とFake観察に基づき、固定できない動的列は例外理由を台帳化する。 | Required | {"items":{"type":["object","null"],"title":"\u30de\u30b9\u30bf\u7a2e\u5225","description":"/admin/master-data \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u30de\u30b9\u30bf\u7a2e\u5225\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `masterTypes` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002","properties":{"table":{"type":["string","null"],"minLength":0,"maxLength":255,"title":"\u30de\u30b9\u30bf\u30c6\u30fc\u30d6\u30eb","description":"/admin/master-data \u306e\u753b\u9762\u8868\u793a\u306b\u4f7f\u3046\u30de\u30b9\u30bf\u30c6\u30fc\u30d6\u30eb\u3002\u696d\u52d9\u30a8\u30f3\u30c6\u30a3\u30c6\u30a3\u305d\u306e\u3082\u306e\u3067\u306f\u306a\u304f\u30c6\u30f3\u30d7\u30ec\u30fc\u30c8/\u4e00\u89a7\u8868\u793a\u306e\u88dc\u52a9\u5024\u3002"},"label":{"type":["string","null"],"minLength":0,"maxLength":255,"title":"\u8868\u793a\u30e9\u30d9\u30eb","description":"/admin/master-data \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u3067\u8868\u793a\u307e\u305f\u306f\u30c6\u30f3\u30d7\u30ec\u30fc\u30c8\u63cf\u753b\u306b\u4f7f\u3046\u8868\u793a\u30e9\u30d9\u30eb\u3002\u540c\u540dproperty\u3067\u3082\u89aa\u6587\u8108 `root` \u306b\u3088\u3063\u3066\u610f\u5473\u3092\u5206\u3051\u308b\u3002"},"value":{"type":["string","null"],"minLength":0,"maxLength":255,"title":"\u30de\u30b9\u30bf\u5024","description":"/admin/master-data \u306e\u30de\u30b9\u30bf\u7a2e\u5225\u307e\u305f\u306f\u30de\u30b9\u30bf\u884c\u306b\u8868\u793a\u3055\u308c\u308b\u5024\u3002\u9078\u629e\u80a2\u306e\u8868\u793a/\u4fdd\u5b58\u5358\u4f4d\u3068\u3057\u3066\u6271\u3046\u3002"}},"$comment":"\u914d\u5217\u8981\u7d20\u306f\u30de\u30b9\u30bf/CSV/option map\u306e\u52d5\u7684\u884c\u3067\u3042\u308a\u3001\u5217\u96c6\u5408\u304c\u5bfe\u8c61\u7a2e\u5225\u306b\u3088\u308a\u5909\u308f\u308b\u305f\u3081\u56fa\u5b9ashape\u5316\u3057\u306a\u3044\u3002\u65e2\u77e5property\u306f\u5951\u7d04\u3057\u3001\u8ffd\u52a0\u5217\u306f\u8a72\u5f53\u30b5\u30fc\u30d3\u30b9\u5883\u754c\u3067\u6271\u3046\u3002"},"minItems":0} |  |
| selectedMaster | string|null | 選択中マスタ - /admin/master-data の処理文脈から派生した選択中マスタ。ALPS基礎語だけでは単位や用途が不足するため、このResource上の意味を明示する。 | Required | {"minLength":0,"maxLength":255} |  |
| rows | array|null | 行データ - /admin/master-data のマスタ/CSV行データ。列集合は対象マスタにより変わるため、既知列を優先して契約する。 | Required | {"items":{"type":["object","null"],"title":"\u884c","description":"/admin/master-data \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u884c\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `rows` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002","properties":{"name":{"type":["string","null"],"minLength":0,"maxLength":32,"title":"\u51e6\u7406\u8868\u793a\u540d","description":"Fake\u89b3\u5bdf\u6587\u5b57\u9577 1\u301c7; \u89b3\u5bdf\u5024 '\u30c6\u30b9\u30c8\u7ba1\u7406\u8005', '\u526f\u7ba1\u7406\u8005', '\u5e97\u8217\u30aa\u30fc\u30ca\u30fc', '\u524a\u9664\u6e08\u307f\u7ba1\u7406\u8005', 'Red', 'Blue', 'S', 'Color'\u3002","example":"\u30c6\u30b9\u30c8\u7ba1\u7406\u8005"},"id":{"type":["string","integer","null"],"title":"ID","description":"Fake\u89b3\u5bdf\u6587\u5b57\u9577 13\u301c32; \u89b3\u5bdf\u5024 'ad000000000000000000000000000001', 'ad000000000000000000000000000003', 'fedcba9876543210fedcba9876543210', '10000000aaaa1111bbbb2222cccc3333', 'ad000000000000000000000000000002', '0123456789abcdef0123456789abcdef', 'aaaaaaaa00000000bbbbbbbb11111111', '20000000dddd2222eeee3333ffff4444'\u3002","example":"ad000000000000000000000000000001","minLength":0,"maxLength":128,"$comment":"Fake\u6587\u5b57\u5217ID\u3068EC-CUBE\u6574\u6570ID\u306e\u4e21\u65b9\u304c\u89b3\u5bdf\u3055\u308c\u308b\u305f\u3081\u3001\u3053\u306e\u5883\u754c\u3060\u3051mixedBoundaryId\u3068\u3057\u3066\u6271\u3046\u3002"}},"$comment":"\u914d\u5217\u8981\u7d20\u306f\u30de\u30b9\u30bf/CSV/option map\u306e\u52d5\u7684\u884c\u3067\u3042\u308a\u3001\u5217\u96c6\u5408\u304c\u5bfe\u8c61\u7a2e\u5225\u306b\u3088\u308a\u5909\u308f\u308b\u305f\u3081\u56fa\u5b9ashape\u5316\u3057\u306a\u3044\u3002\u65e2\u77e5property\u306f\u5951\u7d04\u3057\u3001\u8ffd\u52a0\u5217\u306f\u8a72\u5f53\u30b5\u30fc\u30d3\u30b9\u5883\u754c\u3067\u6271\u3046\u3002"},"minItems":0} |  |

#### Links

| Relation | URL |
|----------|-----|
| doSelectMasterData | [<code>page://self/admin/master-data</code>](/admin/master-data.md) |
## PUT
Selects which master to view (doSelectMasterData). ALPS marks it
`idempotent` → PUT; returns the chosen master's rows.

**ALPS**: `doSelectMasterData`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| masterType | string | マスタ種別（入力） - /admin/master-data の処理文脈から派生したマスタ種別。ALPS基礎語だけでは単位や用途が不足するため、このResource上の意味を明示する。 | tag | Optional | {"minLength":0,"maxLength":255,"default":"tag","$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} |  |


### Response

[Object: PUT /admin/master-data response](../schemas/put-admin-master-data.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| message | string|null | マスタデータメッセージ - /admin/master-data のレスポンスに含まれる処理結果メッセージ。注文時お問い合わせ欄ではなく、画面遷移や完了表示のための通知文。 | Optional | {"minLength":0,"maxLength":32} | 配送は平日希望です。 |
| transitionId | string | ALPS遷移ID - このレスポンス/操作が対応するALPS遷移ID。クライアントの状態遷移追跡に使う。 | Required | {"minLength":2,"maxLength":96,"pattern":"^(go|do)[A-Z][A-Za-z0-9]*$"} | doAddCartItem |
| selectedMaster | string | 選択中マスタ - /admin/master-data の処理文脈から派生した選択中マスタ。ALPS基礎語だけでは単位や用途が不足するため、このResource上の意味を明示する。 | Required | {"minLength":1,"maxLength":255} |  |
| rows | array | 行データ - /admin/master-data のマスタ/CSV行データ。列集合は対象マスタにより変わるため、既知列を優先して契約する。 | Required | {"items":{"type":"object","title":"\u884c","description":"/admin/master-data \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u884c\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `rows` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002","properties":{"name":{"type":["string","null"],"minLength":0,"maxLength":32,"title":"\u51e6\u7406\u8868\u793a\u540d","description":"Fake\u89b3\u5bdf\u6587\u5b57\u9577 1\u301c7; \u89b3\u5bdf\u5024 '\u30c6\u30b9\u30c8\u7ba1\u7406\u8005', '\u526f\u7ba1\u7406\u8005', '\u5e97\u8217\u30aa\u30fc\u30ca\u30fc', '\u524a\u9664\u6e08\u307f\u7ba1\u7406\u8005', 'Red', 'Blue', 'S', 'Color'\u3002","example":"\u30c6\u30b9\u30c8\u7ba1\u7406\u8005"},"value":{"type":["string","null"],"minLength":0,"maxLength":255,"title":"\u30de\u30b9\u30bf\u5024","description":"/admin/master-data \u306e\u30de\u30b9\u30bf\u7a2e\u5225\u307e\u305f\u306f\u30de\u30b9\u30bf\u884c\u306b\u8868\u793a\u3055\u308c\u308b\u5024\u3002\u9078\u629e\u80a2\u306e\u8868\u793a/\u4fdd\u5b58\u5358\u4f4d\u3068\u3057\u3066\u6271\u3046\u3002"},"sortNo":{"type":["integer","null"],"minimum":0,"maximum":2147483647,"title":"\u8868\u793a\u9806","description":"\u4e00\u89a7\u306b\u304a\u3051\u308b\u4e26\u3073\u9806 Fake\u89b3\u5bdf\u6570\u5024 1\u301c20; \u89b3\u5bdf\u5024 '1', '3', '2', '4', '10', '20'\u3002","example":1},"enabled":{"type":["boolean","null"],"title":"\u51e6\u7406\u72b6\u614b\u30d5\u30e9\u30b0","description":"\u89b3\u5bdf\u5024 'true', 'false'\u3002","example":"true"}},"$comment":"\u914d\u5217\u8981\u7d20\u306f\u30de\u30b9\u30bf/CSV/option map\u306e\u52d5\u7684\u884c\u3067\u3042\u308a\u3001\u5217\u96c6\u5408\u304c\u5bfe\u8c61\u7a2e\u5225\u306b\u3088\u308a\u5909\u308f\u308b\u305f\u3081\u56fa\u5b9ashape\u5316\u3057\u306a\u3044\u3002\u65e2\u77e5property\u306f\u5951\u7d04\u3057\u3001\u8ffd\u52a0\u5217\u306f\u8a72\u5f53\u30b5\u30fc\u30d3\u30b9\u5883\u754c\u3067\u6271\u3046\u3002"},"minItems":0} |  |

#### Links

| Relation | URL |
|----------|-----|
| doUpdateMasterData | [<code>page://self/admin/master-data-edit</code>](/admin/master-data-edit.md) |