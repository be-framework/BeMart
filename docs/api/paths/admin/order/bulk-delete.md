<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/order/bulk-delete
EC-CUBE doBulkDeleteOrder — 受注を一括削除する (Wave 9η).

POST /admin/order/bulk-delete

Soft-delete semantics: each targeted row's `orderStatus` flips to
CANCEL(3). ALPS doc says "物理削除" but EC-CUBE keeps the row for
downstream reporting — see {@see \AdminOrdersBulkDeleted} for the
full rationale.

Unknown orderNos are silently skipped; `requestedCount` vs
`changedCount` lets the UI surface stale-grid anomalies. Mirrors
Wave 8 {@see \MyVendor\BeMart\Resource\Page\Admin\ProductBulkStatus}.

Failure mapping:
  - Invalid CSRF                          → 403
  - SemanticVariableException             → 400 (list size / element format)
  - UnauthorizedAdminAccessException      → 403 (no admin session)




## POST
ALPS `doBulkDeleteOrder` に対応する POST 操作。

**ALPS**: `doBulkDeleteOrder` - 受注を一括削除する



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| orderNos | array | 注文番号一覧（入力） - /admin/order/bulk-delete のレスポンスで扱う注文番号一覧。ALPS、Fake観察、Resource境界の形状から導いた業務契約。 | array () | Optional | {"items":{"type":"string","title":"\u6ce8\u6587\u756a\u53f7","minLength":0,"maxLength":64,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation.","description":"/admin/order/bulk-delete \u306e\u5165\u529b\u306b\u542b\u307e\u308c\u308b\u6ce8\u6587\u884c\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `orderNos` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002"},"minItems":0,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} |  |
| ids | array | EC-CUBE-compatible HTML form alias (`ids[]`). | array () | Optional | {"items":{"type":"string","title":"\u6ce8\u6587\u756a\u53f7","minLength":0,"maxLength":64,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation.","description":"/admin/order/bulk-delete \u306eHTML\u30d5\u30a9\u30fc\u30e0\u5165\u529b\u306b\u542b\u307e\u308c\u308b\u6ce8\u6587\u884c\u3002"},"minItems":0,"$comment":"Transport alias only; business contract remains orderNos."} |  |
| mode | string | フォームモード - HTML form submit からのPOSTを識別し、PRG応答にするためのフォーム境界値。 |  | Optional | {"enum":["order_bulk_delete_form",null]} |  |


### Response

[Object: POST /admin/order/bulk-delete response](../schemas/post-admin-order-bulk-delete.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| requestedCount | int|null | 件数 - /admin/order/bulk-delete のレスポンスで返す件数。一覧・集計・処理結果の規模を表す非負整数。 | Required | {"minimum":0,"maximum":2147483647} |  |
| orderNos | array|null | 注文番号一覧 - /admin/order/bulk-delete のレスポンスで扱う注文番号一覧。ALPS、Fake観察、Resource境界の形状から導いた業務契約。 | Required | {"items":{"type":"string","title":"\u6ce8\u6587\u756a\u53f7","minLength":1,"maxLength":64,"pattern":"^[A-Za-z0-9._:-]+$","description":"/admin/order/bulk-delete \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u6ce8\u6587\u884c\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `orderNos` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002"},"minItems":0} |  |
| changedCount | int|null | 件数 - /admin/order/bulk-delete のレスポンスで返す件数。一覧・集計・処理結果の規模を表す非負整数。 | Required | {"minimum":0,"maximum":2147483647} |  |

#### Links

| Relation | URL |
|----------|-----|
| goOrderList | [<code>page://self/admin/order-list</code>](/admin/order-list.md) |