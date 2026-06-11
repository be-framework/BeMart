<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/csv-config
EC-CUBE doUpdateCsv — CSV出力設定を更新する (Wave 9).

POST. Admin replaces the column vector for one csvType (product=1,
customer=2, order=3, shipping=4) — each column carries
`columnName`, `enabled`, `sortNo`. The storage replaces the per-type
row set atomically so the column vector cannot drift.

Wave 9 first iteration scope:
  - persists the configuration (the storage holds it; a subsequent
    read sees the write)
  - the export Finals (Wave 8α product, Wave 8β category, Wave 9
    customer) still emit the hardcoded column list — consuming this
    configuration in the exporters is Phase 2.

Failure mapping:
  - Invalid CSRF                          → 403
  - SemanticVariableException             → 400 (csvType / column shape)
  - UnauthorizedAdminAccessException      → 403 (no admin session)




## GET
EC-CUBE CSV出力項目設定 — Setting/Shop Tier-2.

Thin GET renderer for `Setting/Shop/csv.twig`. The existing POST
persists a submitted vector; this GET serves the editor body.

**ALPS**: `doUpdateCsv`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| csvType | int | CSV種別（入力） - CSV出力項目設定で編集対象にするCSV種別。1=商品CSV, 2=会員CSV, 3=受注CSV, 4=配送CSV。HTTP queryでは文字列として届く場合があるためtransport schemaではstring|integer|nullを許容し、意味検証はResource/Semantic層へ委ねる。 | 3 | Optional | {"default":3,"enum":[1,2,3,4,"1","2","3","4",null],"$comment":"GET query transport boundary: browser selection values arrive as strings; Resource signature casts/validates csvType semantics."} |  |


### Response

[Object: GET /admin/csv-config response](../schemas/get-admin-csv-config.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| form | object|array|null | 入力フォーム - Aura/WebForm由来のフォームオブジェクト。フレームワーク内部構造のためschemaでは存在と型のみを契約する。 | Optional | {"$comment":"Aura/WebForm\u7531\u6765\u306e\u4e0d\u900f\u660e\u30d5\u30a9\u30fc\u30e0\u8868\u73fe\u3002Resource\u5883\u754c\u3067\u306f\u30d5\u30a9\u30fc\u30e0\u306e\u5b58\u5728\u3068\u30b3\u30f3\u30c6\u30ad\u30b9\u30c8\u3060\u3051\u3092\u5951\u7d04\u3057\u3001\u5185\u90e8\u69cb\u9020\u306f\u30d5\u30ec\u30fc\u30e0\u30ef\u30fc\u30af\u5883\u754c\u306b\u59d4\u306d\u308b\u305f\u3081\u8ffd\u52a0\u30ad\u30fc\u5236\u7d04\u3092\u7f6e\u304b\u306a\u3044\u3002"} |  |
| notOutputColumns | object|null | 非出力CSV列 - /admin/csv-config のレスポンスで扱う非出力CSV列。ALPS、Fake観察、Resource境界の形状から導いた業務契約。 | Required | {"properties":{"paymentMethod":{"type":["string","null"],"minLength":0,"maxLength":255,"title":"\u652f\u6255\u65b9\u6cd5","description":"\u6ce8\u6587\u6642\u70b9\u306e\u652f\u6255\u65b9\u6cd5\u540d\u79f0\uff08\u30b9\u30ca\u30c3\u30d7\u30b7\u30e7\u30c3\u30c8\uff09 Fake\u89b3\u5bdf\u6587\u5b57\u9577 4\u301c4; \u89b3\u5bdf\u5024 '\u9280\u884c\u632f\u8fbc'\u3002","example":"\u9280\u884c\u632f\u8fbc","$comment":"CSV\u8a2d\u5b9a\u306e\u5217\u30e9\u30d9\u30eb\u3002property\u540d\u306f\u696d\u52d9\u30d5\u30a3\u30fc\u30eb\u30c9\u3060\u304c\u5024\u306f\u51fa\u529b\u5217\u540d\u306e\u8868\u793a\u6587\u5b57\u5217\u3002"},"trackingNumber":{"type":["string","null"],"title":"\u8377\u7269\u8ffd\u8de1\u756a\u53f7","description":"\u914d\u9001\u696d\u8005\u306e\u8377\u7269\u8ffd\u8de1\u756a\u53f7\u3002confirmUrl\u3068\u7d44\u307f\u5408\u308f\u305b\u3066\u8ffd\u8de1URL\u3092\u69cb\u6210","minLength":0,"maxLength":255,"$comment":"CSV\u8a2d\u5b9a\u306e\u5217\u30e9\u30d9\u30eb\u3002property\u540d\u306f\u696d\u52d9\u30d5\u30a3\u30fc\u30eb\u30c9\u3060\u304c\u5024\u306f\u51fa\u529b\u5217\u540d\u306e\u8868\u793a\u6587\u5b57\u5217\u3002"},"deliveryName":{"type":["string","null"],"minLength":0,"maxLength":255,"title":"\u914d\u9001\u65b9\u6cd5\u540d","description":"\u7ba1\u7406\u753b\u9762\u3067\u767b\u9332\u30fb\u66f4\u65b0\u3059\u308b\u914d\u9001\u65b9\u6cd5\u306e\u8868\u793a\u540d\u3002\u5b9f\u904b\u7528\u3067\u306f\u5e97\u8217\u72ec\u81ea\u306e\u9577\u3044\u540d\u79f0\u3092\u8a31\u5bb9\u3059\u308b\u3002","example":"\u30e4\u30de\u30c8\u5b85\u6025\u4fbf","$comment":"CSV\u8a2d\u5b9a\u306e\u5217\u30e9\u30d9\u30eb\u3002property\u540d\u306f\u696d\u52d9\u30d5\u30a3\u30fc\u30eb\u30c9\u3060\u304c\u5024\u306f\u51fa\u529b\u5217\u540d\u306e\u8868\u793a\u6587\u5b57\u5217\u3002"}},"additionalProperties":false,"required":["paymentMethod","trackingNumber","deliveryName"]} |  |
| outputColumns | object|null | 出力CSV列 - /admin/csv-config のレスポンスで扱う出力CSV列。ALPS、Fake観察、Resource境界の形状から導いた業務契約。 | Required | {"properties":{"orderDate":{"title":"\u6ce8\u6587\u65e5","description":"\u6ce8\u6587\u78ba\u5b9a\u65e5\u6642 Fake\u89b3\u5bdf\u6587\u5b57\u9577 19\u301c19; \u89b3\u5bdf\u5024 '2026-04-01 10:00:00'\u3002","type":"string","example":"2026-04-01 10:00:00","minLength":0,"maxLength":255,"$comment":"CSV\u8a2d\u5b9a\u306e\u5217\u30e9\u30d9\u30eb\u3002property\u540d\u306f\u696d\u52d9\u30d5\u30a3\u30fc\u30eb\u30c9\u3060\u304c\u5024\u306f\u51fa\u529b\u5217\u540d\u306e\u8868\u793a\u6587\u5b57\u5217\u3002"},"paymentTotal":{"type":["string","null"],"title":"\u652f\u6255\u5408\u8a08","description":"\u5b9f\u969b\u306e\u652f\u6255\u91d1\u984d\u3002\u521d\u671f\u5024\u306ftotal\u3068\u540c\u5024\u3067\u3001PointProcessor\u304c\u30dd\u30a4\u30f3\u30c8\u5024\u5f15\u304d\u306eOrderItem\uff08type=POINT_DISCOUNT\u3001\u4e0d\u8ab2\u7a0e\uff09\u3092\u8ffd\u52a0\u5f8c\u306bPurchaseFlow.calculateTotal()\u3067\u518d\u8a08\u7b97\u3055\u308c\u308b\u3002\u8a08\u7b97\u5f0f: total - (\u5229\u7528\u30dd\u30a4\u30f3\u30c8 x pointConversionRate) Fake\u89b3\u5bdf\u6570\u5024 12700\u301c12700; \u89b3\u5bdf\u5024 '12700'\u3002","example":"12700","$comment":"CSV\u8a2d\u5b9a\u306e\u5217\u30e9\u30d9\u30eb\u3002property\u540d\u306f\u696d\u52d9\u30d5\u30a3\u30fc\u30eb\u30c9\u3060\u304c\u5024\u306f\u51fa\u529b\u5217\u540d\u306e\u8868\u793a\u6587\u5b57\u5217\u3002","minLength":0,"maxLength":255},"customerName":{"type":["string","null"],"minLength":0,"maxLength":255,"title":"\u59d3","description":"\u9867\u5ba2\u30fb\u53d7\u6ce8\u30fb\u914d\u9001\u5148\u30fb\u304a\u554f\u3044\u5408\u308f\u305b\u3067\u5171\u901a\u4f7f\u7528\u3055\u308c\u308b\u59d3","$comment":"CSV\u8a2d\u5b9a\u306e\u5217\u30e9\u30d9\u30eb\u3002property\u540d\u306f\u696d\u52d9\u30d5\u30a3\u30fc\u30eb\u30c9\u3060\u304c\u5024\u306f\u51fa\u529b\u5217\u540d\u306e\u8868\u793a\u6587\u5b57\u5217\u3002"},"orderNo":{"type":["string","null"],"minLength":0,"maxLength":255,"title":"\u6ce8\u6587\u756a\u53f7","description":"\u9867\u5ba2\u5411\u3051\u306e\u6ce8\u6587\u756a\u53f7\u3002\u30d5\u30a9\u30fc\u30de\u30c3\u30c8\u306f\u30ab\u30b9\u30bf\u30de\u30a4\u30ba\u53ef\u80fd Fake\u89b3\u5bdf\u6587\u5b57\u9577 32\u301c32; \u89b3\u5bdf\u5024 'past0000000000000000000000000001'\u3002","example":"past0000000000000000000000000001","$comment":"CSV\u8a2d\u5b9a\u306e\u5217\u30e9\u30d9\u30eb\u3002property\u540d\u306f\u696d\u52d9\u30d5\u30a3\u30fc\u30eb\u30c9\u3060\u304c\u5024\u306f\u51fa\u529b\u5217\u540d\u306e\u8868\u793a\u6587\u5b57\u5217\u3002"}},"additionalProperties":false,"required":["orderDate","paymentTotal","customerName","orderNo"]} |  |
| csvType | int | CSV種別 - CSV出力項目設定で現在選択しているCSV種別。1=商品CSV, 2=会員CSV, 3=受注CSV, 4=配送CSV。画面の選択状態とPOST対象の列設定コンテキストを示す。 | Required | {"enum":[1,2,3,4]} | 1 |
| csrfToken | string |  | Required | {"$ref":"#/$defs/csrfToken"} |  |

#### Links

| Relation | URL |
|----------|-----|
| doUpdateCsv | [<code>page://self/admin/csv-config</code>](/admin/csv-config.md) |
## POST
Wave 9: admin-form input. The columns list is sanitized by Be /
Semantic; the column entries themselves carry user-supplied
column names so the taint mark applies to the whole payload.

**ALPS**: `doUpdateCsv`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| csvType | int | CSV種別（入力） - dtb_csv.csv_type_id — mtb_csv_type への FK（1=商品CSV, 2=会員CSV, 3=受注CSV, 4=出荷CSV）。1つの csvType が複数の列設定行（dtb_csv 行）を所有する。doUpdateCsv は1つの csvType の列ベクタ全体を一括 POST し、SqlCsvColumnConfigStorage::replaceType がその csvType の行集合をアトミックに置換する。mtb_csv_type は structure-only ダンプで空のため SQL テストは seedCsvTypes でシード（seedAdminMasters と同じ空マスタ FK シード規約） |  | Required | {"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} |  |
| columns | array | CSV列定義 - /admin/csv-config のCSV列設定。各要素は出力対象フィールドと表示名を表す。 | array () | Optional | {"items":{"type":"object","title":"CSV\u5217\uff08\u5165\u529b\uff09","description":"/admin/csv-config \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308bCSV\u5217\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `columns` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002","properties":{"columnName":{"type":["string","null"],"minLength":0,"maxLength":255,"title":"CSV\u5217\u540d\uff08\u5165\u529b\uff09","description":"doUpdateCsv \u304c\u4fdd\u5b58\u3059\u308bCSV\u5217\u306e\u5185\u90e8\u540d\u3002HTML\u30d5\u30a9\u30fc\u30e0\u3067\u306f columns[n][columnName] \u3068\u3057\u3066\u9001\u4fe1\u3055\u308c\u308b\u3002","$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."},"name":{"type":["string","null"],"minLength":0,"maxLength":32,"title":"\u51e6\u7406\u8868\u793a\u540d\uff08\u5165\u529b\uff09","description":"Fake\u89b3\u5bdf\u6587\u5b57\u9577 1\u301c7; \u89b3\u5bdf\u5024 '\u30c6\u30b9\u30c8\u7ba1\u7406\u8005', '\u526f\u7ba1\u7406\u8005', '\u5e97\u8217\u30aa\u30fc\u30ca\u30fc', '\u524a\u9664\u6e08\u307f\u7ba1\u7406\u8005', 'Red', 'Blue', 'S', 'Color'\u3002","example":"\u30c6\u30b9\u30c8\u7ba1\u7406\u8005","$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."},"label":{"type":["string","null"],"minLength":0,"maxLength":255,"title":"\u8868\u793a\u30e9\u30d9\u30eb\uff08\u5165\u529b\uff09","description":"/admin/csv-config \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u3067\u8868\u793a\u307e\u305f\u306f\u30c6\u30f3\u30d7\u30ec\u30fc\u30c8\u63cf\u753b\u306b\u4f7f\u3046\u8868\u793a\u30e9\u30d9\u30eb\u3002\u540c\u540dproperty\u3067\u3082\u89aa\u6587\u8108 `root` \u306b\u3088\u3063\u3066\u610f\u5473\u3092\u5206\u3051\u308b\u3002","$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."},"value":{"type":["string","null"],"minLength":0,"maxLength":255,"title":"\u8868\u793a\u5024","description":"/admin/csv-config \u306e\u5165\u529b\u3067\u8868\u793a\u307e\u305f\u306f\u9078\u629e\u80a2\u3068\u3057\u3066\u4f7f\u3046\u5024\u3002\u89aa\u30b3\u30f3\u30c6\u30ad\u30b9\u30c8 `columnsItem` \u306b\u5c5e\u3059\u308b\u3002","$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."},"enabled":{"type":["boolean","integer","string","null"],"title":"\u51e6\u7406\u72b6\u614b\u30d5\u30e9\u30b0\uff08\u5165\u529b\uff09","description":"\u89b3\u5bdf\u5024 'true', 'false'\u3002","example":"true","$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."},"sortNo":{"type":["integer","string","null"],"title":"CSV\u5217\u9806\uff08\u5165\u529b\uff09","description":"doUpdateCsv \u306e\u5217\u9806\u3002HTML\u30d5\u30a9\u30fc\u30e0\u3067\u306f\u6587\u5b57\u5217\u3068\u3057\u3066\u9001\u4fe1\u3055\u308c\u308b\u3002","$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."}},"$comment":"\u914d\u5217\u8981\u7d20\u306f\u30de\u30b9\u30bf/CSV/option map\u306e\u52d5\u7684\u884c\u3067\u3042\u308a\u3001\u5217\u96c6\u5408\u304c\u5bfe\u8c61\u7a2e\u5225\u306b\u3088\u308a\u5909\u308f\u308b\u305f\u3081\u56fa\u5b9ashape\u5316\u3057\u306a\u3044\u3002\u65e2\u77e5property\u306f\u5951\u7d04\u3057\u3001\u8ffd\u52a0\u5217\u306f\u8a72\u5f53\u30b5\u30fc\u30d3\u30b9\u5883\u754c\u3067\u6271\u3046\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."},"minItems":0,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} |  |
| csvOutput |  | CSV出力対象列（HTML入力） - CSV設定画面の multi-select `csvOutput[]` が送る出力対象列。JavaScript が生成する columns[n] と同じ列ベクタへ Resource 境界で正規化される。 | array () | Optional | {"items":{"type":["string","null"],"minLength":0,"maxLength":255},"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} |  |
| csvNotOutput |  | CSV非出力対象列（HTML入力） - CSV設定画面の multi-select `csvNotOutput[]` が送る非出力対象列。JavaScript が生成する columns[n] と同じ列ベクタへ Resource 境界で正規化される。 | array () | Optional | {"items":{"type":["string","null"],"minLength":0,"maxLength":255},"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} |  |


### Response

[Object: POST /admin/csv-config response](../schemas/post-admin-csv-config.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| message | string|null | CSVメッセージ - /admin/csv-config のレスポンスに含まれる処理結果メッセージ。注文時お問い合わせ欄ではなく、画面遷移や完了表示のための通知文。 | Optional | {"minLength":0,"maxLength":32} | 配送は平日希望です。 |
| csvType | int|null | CSV種別 - dtb_csv.csv_type_id — mtb_csv_type への FK（1=商品CSV, 2=会員CSV, 3=受注CSV, 4=出荷CSV）。1つの csvType が複数の列設定行（dtb_csv 行）を所有する。doUpdateCsv は1つの csvType の列ベクタ全体を一括 POST し、SqlCsvColumnConfigStorage::replaceType がその csvType の行集合をアトミックに置換する。mtb_csv_type は structure-only ダンプで空のため SQL テストは seedCsvTypes でシード（seedAdminMasters と同じ空マスタ FK シード規約） | Required | {"minimum":0} |  |
| columns | array | CSV列定義 - /admin/csv-config のCSV列設定。各要素は出力対象フィールドと表示名を表す。 | Required | {"items":{"type":"object","title":"CSV\u5217","description":"/admin/csv-config \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308bCSV\u5217\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `columns` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002","properties":{"name":{"type":["string","null"],"minLength":0,"maxLength":32,"title":"\u51e6\u7406\u8868\u793a\u540d","description":"Fake\u89b3\u5bdf\u6587\u5b57\u9577 1\u301c7; \u89b3\u5bdf\u5024 '\u30c6\u30b9\u30c8\u7ba1\u7406\u8005', '\u526f\u7ba1\u7406\u8005', '\u5e97\u8217\u30aa\u30fc\u30ca\u30fc', '\u524a\u9664\u6e08\u307f\u7ba1\u7406\u8005', 'Red', 'Blue', 'S', 'Color'\u3002","example":"\u30c6\u30b9\u30c8\u7ba1\u7406\u8005"},"label":{"type":["string","null"],"minLength":0,"maxLength":255,"title":"\u8868\u793a\u30e9\u30d9\u30eb","description":"/admin/csv-config \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u3067\u8868\u793a\u307e\u305f\u306f\u30c6\u30f3\u30d7\u30ec\u30fc\u30c8\u63cf\u753b\u306b\u4f7f\u3046\u8868\u793a\u30e9\u30d9\u30eb\u3002\u540c\u540dproperty\u3067\u3082\u89aa\u6587\u8108 `root` \u306b\u3088\u3063\u3066\u610f\u5473\u3092\u5206\u3051\u308b\u3002"},"value":{"type":["string","null"],"minLength":0,"maxLength":255,"title":"\u8868\u793a\u5024","description":"/admin/csv-config \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u3067\u8868\u793a\u307e\u305f\u306f\u9078\u629e\u80a2\u3068\u3057\u3066\u4f7f\u3046\u5024\u3002\u89aa\u30b3\u30f3\u30c6\u30ad\u30b9\u30c8 `columnsItem` \u306b\u5c5e\u3059\u308b\u3002"},"enabled":{"type":["boolean","null"],"title":"\u51e6\u7406\u72b6\u614b\u30d5\u30e9\u30b0","description":"\u89b3\u5bdf\u5024 'true', 'false'\u3002","example":"true"}},"$comment":"\u914d\u5217\u8981\u7d20\u306f\u30de\u30b9\u30bf/CSV/option map\u306e\u52d5\u7684\u884c\u3067\u3042\u308a\u3001\u5217\u96c6\u5408\u304c\u5bfe\u8c61\u7a2e\u5225\u306b\u3088\u308a\u5909\u308f\u308b\u305f\u3081\u56fa\u5b9ashape\u5316\u3057\u306a\u3044\u3002\u65e2\u77e5property\u306f\u5951\u7d04\u3057\u3001\u8ffd\u52a0\u5217\u306f\u8a72\u5f53\u30b5\u30fc\u30d3\u30b9\u5883\u754c\u3067\u6271\u3046\u3002"},"minItems":0} |  |
| count | int|null | 件数 - /admin/csv-config のレスポンスで返す件数。一覧・集計・処理結果の規模を表す非負整数。 | Required | {"minimum":0,"maximum":2147483647} |  |

#### Links

| Relation | URL |
|----------|-----|
| goTop | [<code>page://self/admin</code>](/admin.md) |
| goExportProduct | [<code>page://self/admin/product-csv</code>](/admin/product-csv.md) |