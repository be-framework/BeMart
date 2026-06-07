<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/category/csv
EC-CUBE goExportCategory + doImportCategoryCsv — CSV endpoint
(Wave 7).

- GET  → goExportCategory   (RFC 4180 dump — admin AUTHZ)
  - POST → doImportCategoryCsv (**Phase 2 stub** — accepts the body
                                but does not persist; ALPS/AUTHZ
                                contract is exercised, full parser
                                deferred)

Both methods enforce the admin firewall. The stubbed import path
returns `accepted=false` with an explanatory notice so callers
cannot mistake the stub for a real import.




## GET
ALPS `goExportCategory` に対応する GET 操作。

**ALPS**: `goExportCategory`



### Request

_No parameters required_

### Response

[Object: GET /admin/category/csv response](../schemas/get-admin-category-csv.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| rowCount | int|null | 行数 - /admin/category/csv のレスポンスで返す行数。一覧・集計・処理結果の規模を表す非負整数。 | Required | {"minimum":0,"maximum":2147483647} |  |
| csv | string|null | 輸送ペイロード - CSVインポート/エクスポート本文。列構造の詳細はCSV互換サービス境界で検査する。 | Required | {"minLength":0,"maxLength":5000000,"$comment":"CSV\u5217\u306e\u696d\u52d9\u59a5\u5f53\u6027\u306fCSV\u4e92\u63db\u30b5\u30fc\u30d3\u30b9\u3067\u691c\u67fb\u3059\u308b\u3002\u3053\u3053\u3067\u306fJSON\u5883\u754c\u4e0a\u306e\u6587\u5b57\u5217\u30b5\u30a4\u30ba\u3092\u5951\u7d04\u3059\u308b\u3002"} |  |

#### Links

| Relation | URL |
|----------|-----|
| goCategoryList | [<code>page://self/admin/category/category-list</code>](/admin/category/category-list.md) |
| doImportCategoryCsv | [<code>page://self/admin/category/csv</code>](/admin/category/csv.md) |
## POST
ALPS `doImportCategoryCsv` に対応する POST 操作。

**ALPS**: `doImportCategoryCsv`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| csv | string | 輸送ペイロード（入力） - CSVインポート/エクスポート本文。列構造の詳細はCSV互換サービス境界で検査する。 |  | Required | {"minLength":0,"maxLength":5000000,"$comment":"CSV\u5217\u306e\u696d\u52d9\u59a5\u5f53\u6027\u306fCSV\u4e92\u63db\u30b5\u30fc\u30d3\u30b9\u3067\u691c\u67fb\u3059\u308b\u3002\u3053\u3053\u3067\u306fJSON\u5883\u754c\u4e0a\u306e\u6587\u5b57\u5217\u30b5\u30a4\u30ba\u3092\u5951\u7d04\u3059\u308b\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} |  |


### Response

[Object: POST /admin/category/csv response](../schemas/post-admin-category-csv.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| deleted | int|boolean|null | 削除件数 - /admin/category/csv の処理状態を示す削除件数。画面表示や冪等処理結果の分岐に使う真偽値。 | Required | {"minimum":0} |  |
| imported | int|boolean|null | 取込件数 - /admin/category/csv の処理状態を示す取込件数。画面表示や冪等処理結果の分岐に使う真偽値。 | Required | {"minimum":0} |  |
| message | string|null | CSVメッセージ - /admin/category/csv のレスポンスに含まれる処理結果メッセージ。注文時お問い合わせ欄ではなく、画面遷移や完了表示のための通知文。 | Optional | {"minLength":0,"maxLength":32} | 配送は平日希望です。 |
| transitionId | string | ALPS遷移ID - このレスポンス/操作が対応するALPS遷移ID。クライアントの状態遷移追跡に使う。 | Required | {"minLength":2,"maxLength":96,"pattern":"^(go|do)[A-Z][A-Za-z0-9]*$"} | doAddCartItem |
| accepted | int|boolean|null | 受理件数 - /admin/category/csv の処理状態を示す受理件数。画面表示や冪等処理結果の分岐に使う真偽値。 | Required | {"minimum":0} |  |
| lineCount | int|null | CSV行数 - /admin/category/csv のレスポンスで返すCSV行数。一覧・集計・処理結果の規模を表す非負整数。 | Required | {"minimum":0,"maximum":2147483647} |  |

#### Links

| Relation | URL |
|----------|-----|
| goCategoryList | [<code>page://self/admin/category/category-list</code>](/admin/category/category-list.md) |
| goExportOrder | [<code>page://self/admin/order/export-order</code>](/admin/order/export-order.md) |