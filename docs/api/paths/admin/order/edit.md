<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/order/edit
EC-CUBE 受注登録 / 受注編集 — Order Tier-2 (`admin/Order/edit.twig`,
the ~1057-line multi-panel order editor).

GET /admin/order/edit            → blank "new order" editor
  GET /admin/order/edit?orderNo=…  → editor pre-filled for one order

Thin GET renderer. The sibling JSON resource {@see \MyVendor\BeMart\Resource\Page\Admin\Order}
carries the `goOrder` read + `doUpdateOrder` write; this resource is
the HTML editor shell only. An empty `$orderNo` renders the blank
editor (EC-CUBE's "受注登録" path — the render-smoke test exercises
this with empty JSON-backed fake storage); a known orderNo pre-fills; an unknown
orderNo is 404.

AUTHZ: the blank-editor path checks the admin session directly
(Pattern B — no Be transition is invoked when there is no order to
read); the pre-fill path delegates to {@see \AdminOrderFetched}, which
raises {@see \UnauthorizedAdminAccessException} for a non-admin
firewall. Both surface 403.




## GET
ALPS `goOrder` に対応する GET 操作。

**ALPS**: `goOrder` - 受注詳細を見る



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| orderNo | string | 注文番号（入力） - 顧客向けの注文番号。フォーマットはカスタマイズ可能 Fake観察文字長 32〜32; 観察値 'past0000000000000000000000000001'。 |  | Optional | {"minLength":0,"maxLength":64,"default":"","$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | past0000000000000000000000000001 |


### Response

[Object: GET /admin/order/edit response](../schemas/get-admin-order-edit.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| form | object|array|null | 入力フォーム - Aura/WebForm由来のフォームオブジェクト。フレームワーク内部構造のためschemaでは存在と型のみを契約する。 | Optional | {"$comment":"Aura/WebForm\u7531\u6765\u306e\u4e0d\u900f\u660e\u30d5\u30a9\u30fc\u30e0\u8868\u73fe\u3002Resource\u5883\u754c\u3067\u306f\u30d5\u30a9\u30fc\u30e0\u306e\u5b58\u5728\u3068\u30b3\u30f3\u30c6\u30ad\u30b9\u30c8\u3060\u3051\u3092\u5951\u7d04\u3057\u3001\u5185\u90e8\u69cb\u9020\u306f\u30d5\u30ec\u30fc\u30e0\u30ef\u30fc\u30af\u5883\u754c\u306b\u59d4\u306d\u308b\u305f\u3081\u8ffd\u52a0\u30ad\u30fc\u5236\u7d04\u3092\u7f6e\u304b\u306a\u3044\u3002"} |  |
| order | array|null|object | 注文詳細 - /admin/order/edit のレスポンスで扱う注文詳細。配列要素はALPS意味とFake観察に基づき、固定できない動的列は例外理由を台帳化する。 | Required | {"items":{"type":"string","title":"\u6ce8\u6587","minLength":0,"maxLength":255,"description":"/admin/order/edit \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u6ce8\u6587\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `order` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002"},"minItems":0,"$comment":"\u5358\u4e00\u8a73\u7d30\u753b\u9762\u3067\u306f\u672a\u9078\u629e/\u521d\u671f\u8868\u793a\u306b\u7a7a\u914d\u5217\u3001\u53d6\u5f97\u6e08\u307f\u72b6\u614b\u306bobject\u304c\u73fe\u308c\u308b\u3002\u4e0d\u900f\u660e\u306a\u8a73\u7d30\u69cb\u9020\u306f\u65e2\u77e5property\u3092\u512a\u5148\u3057\u3001\u8ffd\u52a0\u30ad\u30fc\u306f\u4e92\u63db\u5883\u754c\u3068\u3057\u3066\u8a31\u5bb9\u3059\u308b\u3002"} |  |
| items | array|null | 明細一覧 - /admin/order/edit の親オブジェクト `` に含まれる明細配列。商品・カート・受注明細の文脈で解釈する。 | Required | {"items":{"type":["object","null"],"title":"\u660e\u7d30","description":"/admin/order/edit \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u660e\u7d30\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `items` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002","properties":{"productName":{"type":["string","null"],"minLength":0,"maxLength":128,"title":"\u5546\u54c1\u540d","description":"\u5546\u54c1\u306e\u8868\u793a\u540d Fake\u89b3\u5bdf\u6587\u5b57\u9577 6\u301c17\u3002","example":"\u30b5\u30f3\u30d7\u30eb\u5546\u54c1 A"},"productCode":{"title":"\u5546\u54c1\u30b3\u30fc\u30c9","description":"SKU/\u54c1\u756a\u3002\u5728\u5eab\u7ba1\u7406\u3084\u53d7\u6ce8\u660e\u7d30\u3067\u306e\u8b58\u5225\u306b\u4f7f\u7528 \u5546\u54c1\u3092\u8b58\u5225\u3059\u308bSKU\u3002Fake corpus\u3067\u306fASCII\u82f1\u6570\u30fb\u30cf\u30a4\u30d5\u30f3\u4e2d\u5fc3\u3067\u3001\u53d7\u6ce8\u660e\u7d30/\u30ab\u30fc\u30c8\u660e\u7d30\u306e\u7d50\u5408\u30ad\u30fc\u306b\u306a\u308b\u3002 Fake\u89b3\u5bdf\u6587\u5b57\u9577 10\u301c26\u3002","type":"string","minLength":0,"maxLength":64,"example":"sample-001"},"quantity":{"title":"\u6570\u91cf","description":"\u8cfc\u5165\u6570\u91cf\u3002\u30ab\u30fc\u30c8\u660e\u7d30\u3068\u53d7\u6ce8\u660e\u7d30\u3067\u5171\u901a\u4f7f\u7528 Fake\u89b3\u5bdf\u6570\u5024 1\u301c3; \u89b3\u5bdf\u5024 '1', '2', '3'\u3002","type":"integer","minimum":1,"maximum":999,"example":1},"unitPrice":{"title":"\u5358\u4fa1\uff08\u8868\u793a/\u8a08\u7b97\u7528\uff09","description":"\u660e\u7d301\u4ef6\u3042\u305f\u308a\u306e\u5358\u4fa1\u3002\u53d7\u6ce8/\u30ab\u30fc\u30c8\u660e\u7d30\u30fb\u304a\u6c17\u306b\u5165\u308a\u30b9\u30ca\u30c3\u30d7\u30b7\u30e7\u30c3\u30c8\u3067\u306f\u8ffd\u52a0\u6642\u70b9\u306e price02 \u3092\u30b9\u30ca\u30c3\u30d7\u30b7\u30e7\u30c3\u30c8\u3057\u3066\u4fdd\u6301\u3059\u308b\uff08\u5f8c\u306e\u5024\u5f15\u304d\u3084\u30de\u30b9\u30bf\u6539\u5b9a\u306b\u5f71\u97ff\u3055\u308c\u306a\u3044\uff09\u3002BeMart \u5074\u3067\u306f `int` \u5186\u6574\u6570 Fake\u89b3\u5bdf\u6570\u5024 1200\u301c9800; \u89b3\u5bdf\u5024 '1200', '9800'\u3002","type":"integer","minimum":0,"maximum":999999999,"example":1200}},"additionalProperties":false,"$comment":"\u914d\u5217\u8981\u7d20\u306fFake/Resource\u3067\u89b3\u5bdf\u3055\u308c\u305f\u65e2\u77e5property\u306b\u56fa\u5b9a\u3059\u308b\u3002\u65b0\u3057\u3044\u5217\u304c\u5fc5\u8981\u306b\u306a\u3063\u305f\u5834\u5408\u306fSemantic-Ex\u89b3\u5bdf\u306b\u8ffd\u52a0\u3057\u3066schema\u3092\u66f4\u65b0\u3059\u308b\u3002"},"minItems":0} |  |
| orderNo | string|null | 注文番号 - 顧客向けの注文番号。フォーマットはカスタマイズ可能 Fake観察文字長 32〜32; 観察値 'past0000000000000000000000000001'。 | Required | {"minLength":0,"maxLength":64} | past0000000000000000000000000001 |
| csrfToken | string|null | CSRFトークン - 受注登録/編集フォーム送信用のCSRFトークン。Resource bodyからTwig hidden inputへ渡される。 | Optional | {"minLength":0,"maxLength":160,"pattern":"^[A-Za-z0-9_.:-]*$","$comment":"CSRF\u5024\u306fCsrfProtected\u5883\u754c\u306e\u8cac\u52d9\u3002GET body\u3067\u306f\u30d5\u30a9\u30fc\u30e0\u6587\u8108\u3092\u793a\u3059\u305f\u3081\u306bnull\u3092\u8a31\u5bb9\u3059\u308b\u3002"} |  |

#### Links

| Relation | URL |
|----------|-----|
| doUpdateOrder | [<code>page://self/admin/order</code>](/admin/order.md) |
| goOrderList | [<code>page://self/admin/order-list</code>](/admin/order-list.md) |