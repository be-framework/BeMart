<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/order/import-shipping
EC-CUBE doImportShippingCsv — 配送CSVをインポートする (Wave 9η).

POST /admin/order/import-shipping

Accepts the CSV body as a plain string and updates tracking numbers
for existing orders. Unknown order rows are counted as skipped.

Failure mapping:
  - Invalid CSRF                          → 403
  - UnauthorizedAdminAccessException      → 403 (no admin session)




## GET
EC-CUBE 出荷CSV登録 — Order Tier-2.

Thin GET renderer for `admin/Order/csv_shipping.twig` — the
shipping-CSV upload form. The POST below accepts the uploaded
CSV; this GET serves the upload-form shell. AUTHZ is a direct
admin-session check (Pattern B — no Be transition is invoked on
the GET path); a non-admin firewall is refused with 403.

**ALPS**: `doImportShippingCsv`



### Request

_No parameters required_

### Response

[Object: GET /admin/order/import-shipping response](../schemas/get-admin-order-import-shipping.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|

#### Links

| Relation | URL |
|----------|-----|
| doImportShippingCsv | [<code>page://self/admin/order/import-shipping</code>](/admin/order/import-shipping.md) |
| goExportShipping | [<code>page://self/admin/order/export-shipping</code>](/admin/order/export-shipping.md) |
## POST
ALPS `doImportShippingCsv` に対応する POST 操作。

**ALPS**: `doImportShippingCsv`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| csv | string | 輸送ペイロード（入力） - CSVインポート/エクスポート本文。列構造の詳細はCSV互換サービス境界で検査する。 |  | Required | {"minLength":0,"maxLength":5000000,"$comment":"CSV\u5217\u306e\u696d\u52d9\u59a5\u5f53\u6027\u306fCSV\u4e92\u63db\u30b5\u30fc\u30d3\u30b9\u3067\u691c\u67fb\u3059\u308b\u3002\u3053\u3053\u3067\u306fJSON\u5883\u754c\u4e0a\u306e\u6587\u5b57\u5217\u30b5\u30a4\u30ba\u3092\u5951\u7d04\u3059\u308b\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} |  |


### Response

[Object: POST /admin/order/import-shipping response](../schemas/post-admin-order-import-shipping.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| skipped | int|boolean|null | スキップ件数 - CSV取込で業務的に処理対象外となった行数。旧境界では真偽値も返るため互換的に許容する。 | Required | {"minimum":0,"$comment":"CSV import response context\u3067\u306f skipped \u306fboolean\u3067\u306f\u306a\u304f\u4ef6\u6570\u3092\u8868\u3059\u3002\u65e7Fake/Resource\u5883\u754c\u306eboolean\u4e92\u63db\u3060\u3051\u6b8b\u3059\u3002"} | 0 |
| imported | int|boolean|null | 取込件数 - /admin/order/import-shipping の処理状態を示す取込件数。画面表示や冪等処理結果の分岐に使う真偽値。 | Required | {"minimum":0} |  |
| message | string|null | CSVメッセージ - /admin/order/import-shipping のレスポンスに含まれる処理結果メッセージ。注文時お問い合わせ欄ではなく、画面遷移や完了表示のための通知文。 | Optional | {"minLength":0,"maxLength":32} | 配送は平日希望です。 |
| transitionId | string | ALPS遷移ID - このレスポンス/操作が対応するALPS遷移ID。クライアントの状態遷移追跡に使う。 | Required | {"minLength":2,"maxLength":96,"pattern":"^(go|do)[A-Z][A-Za-z0-9]*$"} | doAddCartItem |
| accepted | int|boolean|null | 受理件数 - /admin/order/import-shipping の処理状態を示す受理件数。画面表示や冪等処理結果の分岐に使う真偽値。 | Required | {"minimum":0} |  |
| lineCount | int|null | CSV行数 - /admin/order/import-shipping のレスポンスで返すCSV行数。一覧・集計・処理結果の規模を表す非負整数。 | Required | {"minimum":0,"maximum":2147483647} |  |

#### Links

| Relation | URL |
|----------|-----|
| goOrderList | [<code>page://self/admin/order-list</code>](/admin/order-list.md) |
| goExportShipping | [<code>page://self/admin/order/export-shipping</code>](/admin/order/export-shipping.md) |
| goExportCustomer | [<code>page://self/admin/customer-csv</code>](/admin/customer-csv.md) |