<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/product-bulk-status
EC-CUBE doBulkUpdateProductStatus — 商品ステータスを一括変更する
(Wave 8 admin).

onPost only. CSRF enforced. The Final silently skips unknown codes;
`requestedCount` vs `changedCount` lets the UI surface anomalies
(a stale grid row, an already-aligned status, etc.).




## POST
ALPS `doBulkUpdateProductStatus` に対応する POST 操作。

**ALPS**: `doBulkUpdateProductStatus`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| productCodes | array | 取込商品コード一覧 - 商品CSV取込で処理対象になったSKU一覧。各要素は商品コード制約に従う。 |  | Required | {"items":{"title":"\u5546\u54c1\u30b3\u30fc\u30c9","description":"SKU/\u54c1\u756a\u3002\u5728\u5eab\u7ba1\u7406\u3084\u53d7\u6ce8\u660e\u7d30\u3067\u306e\u8b58\u5225\u306b\u4f7f\u7528 SKU\u3068\u3057\u3066\u5728\u5eab\u30fb\u30ab\u30fc\u30c8\u30fb\u53d7\u6ce8\u660e\u7d30\u3092\u63a5\u7d9a\u3059\u308b\u3002Fake\u89b3\u5bdf\u3067\u306fASCII\u82f1\u6570\u3068\u30cf\u30a4\u30d5\u30f3\u4e2d\u5fc3\u3002","type":"string","minLength":0,"maxLength":64,"example":"sample-001","$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."},"minItems":0,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} |  |
| productStatus | int | 商品ステータス（入力） - 1=公開（フロント表示）, 2=非公開（フロント非表示）, 3=廃止（論理削除、管理画面でもデフォルト非表示） Fake観察数値 1〜3; 観察値 '1', '2', '3'。 |  | Required | {"$comment":"\u5546\u54c1\u30b9\u30c6\u30fc\u30bf\u30b9\uff08\u5165\u529b\uff09\u306f\u672c\u6765\u6570\u5024/\u5217\u6319\u306e\u696d\u52d9\u5024\u3060\u304c\u3001HTTP\u30d5\u30a9\u30fc\u30e0\u3067\u306f\u6587\u5b57\u5217\u3068\u3057\u3066\u5c4a\u304f\u3002Resource/Semantic\u5c64\u306e400\u5fdc\u7b54\u3092\u596a\u308f\u306a\u3044\u305f\u3081transport schema\u3067\u306f\u6587\u5b57\u5217\u5165\u529b\u3092\u8a31\u5bb9\u3059\u308b\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 1 |


### Response

[Object: POST /admin/product-bulk-status response](../schemas/post-admin-product-bulk-status.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| requestedCount | int|null | 件数 - /admin/product-bulk-status のレスポンスで返す件数。一覧・集計・処理結果の規模を表す非負整数。 | Required | {"minimum":0,"maximum":2147483647} |  |
| changedCount | int|null | 件数 - /admin/product-bulk-status のレスポンスで返す件数。一覧・集計・処理結果の規模を表す非負整数。 | Required | {"minimum":0,"maximum":2147483647} |  |
| productCodes | array | 取込商品コード一覧 - 商品CSV取込で処理対象になったSKU一覧。各要素は商品コード制約に従う。 | Required | {"items":{"title":"\u5546\u54c1\u30b3\u30fc\u30c9","description":"SKU/\u54c1\u756a\u3002\u5728\u5eab\u7ba1\u7406\u3084\u53d7\u6ce8\u660e\u7d30\u3067\u306e\u8b58\u5225\u306b\u4f7f\u7528 SKU\u3068\u3057\u3066\u5728\u5eab\u30fb\u30ab\u30fc\u30c8\u30fb\u53d7\u6ce8\u660e\u7d30\u3092\u63a5\u7d9a\u3059\u308b\u3002Fake\u89b3\u5bdf\u3067\u306fASCII\u82f1\u6570\u3068\u30cf\u30a4\u30d5\u30f3\u4e2d\u5fc3\u3002","type":"string","minLength":1,"maxLength":64,"pattern":"^[A-Za-z0-9][A-Za-z0-9._-]{0,63}$","example":"sample-001"},"minItems":0} |  |
| productStatus | int|null | 商品ステータス - 1=公開（フロント表示）, 2=非公開（フロント非表示）, 3=廃止（論理削除、管理画面でもデフォルト非表示） Fake観察数値 1〜3; 観察値 '1', '2', '3'。 | Required | {"enum":[1,2,3]} | 1 |

#### Links

| Relation | URL |
|----------|-----|
| goProductList | [<code>page://self/admin/product-list</code>](/admin/product-list.md) |