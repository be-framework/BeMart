<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /mypage/order-history
EC-CUBE goOrderHistory — 注文履歴一覧 (Mypage/OrderHistory).

Safe read. No CSRF (read-only). AUTHN is enforced in the Be layer: the
customer's full order history is surfaced from {@see \CustomerSession}'s
customerId, so request-parameter tampering cannot widen the scope to
another customer's orders.

Distinct from `page://self/mypage` (the dashboard, which only carries
the most recent 5 orders for the summary panel): this resource is the
unbounded view, paged by `historyLimit` + `offset`.

Failure mapping:
  - SemanticVariableException → 400 (limit / offset out of range)
  - UnauthenticatedException  → 401 (no / stale session)




## GET
ALPS `goOrderHistory` に対応する GET 操作。

**ALPS**: `goOrderHistory`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| historyLimit | int | 一覧制御項目（入力） - /mypage/order-history のレスポンスで返す一覧制御項目。一覧、集計、CSV処理結果の規模を表す非負の数値。 | 50 | Optional | {"default":50,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} |  |
| offset | int | 開始位置（入力） - /mypage/order-history の一覧表示を制御するページング/検索条件。件数、開始位置、並び順、前後リンクをクライアントが再現するための値。 | 0 | Optional | {"default":0,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} |  |


### Response

[Object: GET /mypage/order-history response](../schemas/get-mypage-order-history.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| offset | int|null | 開始位置 - /mypage/order-history の一覧表示を制御するページング/検索条件。件数、開始位置、並び順、前後リンクをクライアントが再現するための値。 | Required | {"minimum":0,"maximum":2147483647} |  |
| limit | int|null | 表示件数 - /mypage/order-history の一覧表示を制御するページング/検索条件。件数、開始位置、並び順、前後リンクをクライアントが再現するための値。 | Required | {"minimum":0,"maximum":2147483647} |  |
| orders | array|null | 注文一覧 - /mypage/order-history のレスポンスで扱う注文一覧。配列要素はALPS意味とFake観察に基づき、固定できない動的列は例外理由を台帳化する。 | Required | {"items":{"type":["object","null"],"title":"\u6ce8\u6587\u6982\u8981","description":"/mypage/order-history \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u6ce8\u6587\u6982\u8981\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `orders` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002","properties":{"orderDate":{"title":"\u6ce8\u6587\u65e5","description":"\u6ce8\u6587\u78ba\u5b9a\u65e5\u6642 Fake\u89b3\u5bdf\u6587\u5b57\u9577 19\u301c19; \u89b3\u5bdf\u5024 '2026-04-01 10:00:00'\u3002","type":"string","example":"2026-04-01 10:00:00","pattern":"^\\d{4}-\\d{2}-\\d{2}([ T]\\d{2}:\\d{2}:\\d{2}([+-]\\d{2}:?\\d{2}|Z)?)?$"},"paymentTotal":{"type":["integer","null"],"title":"\u652f\u6255\u5408\u8a08","description":"\u5b9f\u969b\u306e\u652f\u6255\u91d1\u984d\u3002\u521d\u671f\u5024\u306ftotal\u3068\u540c\u5024\u3067\u3001PointProcessor\u304c\u30dd\u30a4\u30f3\u30c8\u5024\u5f15\u304d\u306eOrderItem\uff08type=POINT_DISCOUNT\u3001\u4e0d\u8ab2\u7a0e\uff09\u3092\u8ffd\u52a0\u5f8c\u306bPurchaseFlow.calculateTotal()\u3067\u518d\u8a08\u7b97\u3055\u308c\u308b\u3002\u8a08\u7b97\u5f0f: total - (\u5229\u7528\u30dd\u30a4\u30f3\u30c8 x pointConversionRate) Fake\u89b3\u5bdf\u6570\u5024 12700\u301c12700; \u89b3\u5bdf\u5024 '12700'\u3002","example":12700,"minimum":0,"maximum":999999999},"paymentDate":{"title":"\u5165\u91d1\u65e5","description":"\u5165\u91d1\u78ba\u8a8d\u65e5\u6642\u3002\u5165\u91d1\u6e08\u307f\u30b9\u30c6\u30fc\u30bf\u30b9\u3078\u306e\u5909\u66f4\u6642\u306b\u8a18\u9332 Fake\u89b3\u5bdf\u6587\u5b57\u9577 19\u301c19; \u89b3\u5bdf\u5024 '2026-04-01 10:00:00'\u3002","type":"string","example":"2026-04-01 10:00:00","$comment":"\u672a\u5165\u91d1\u30fb\u672a\u767a\u9001\u30fb\u672a\u516c\u958b\u306a\u3069\u672a\u78ba\u5b9a\u65e5\u6642\u306fEC-CUBE\u5883\u754c\u3067\u7a7a\u6587\u5b57\u3068\u3057\u3066\u73fe\u308c\u308b\u305f\u3081\u3001\u65e5\u4ed8/\u65e5\u6642\u6587\u5b57\u5217\u306b\u52a0\u3048\u3066\u7a7a\u6587\u5b57\u3092\u8a31\u5bb9\u3059\u308b\u3002","pattern":"^$|\\d{4}-\\d{2}-\\d{2}([ T]\\d{2}:\\d{2}:\\d{2}([+-]\\d{2}:?\\d{2}|Z)?)?$"},"orderNo":{"type":["string","null"],"minLength":0,"maxLength":64,"title":"\u6ce8\u6587\u756a\u53f7","description":"\u9867\u5ba2\u5411\u3051\u306e\u6ce8\u6587\u756a\u53f7\u3002\u30d5\u30a9\u30fc\u30de\u30c3\u30c8\u306f\u30ab\u30b9\u30bf\u30de\u30a4\u30ba\u53ef\u80fd Fake\u89b3\u5bdf\u6587\u5b57\u9577 32\u301c32; \u89b3\u5bdf\u5024 'past0000000000000000000000000001'\u3002","example":"past0000000000000000000000000001"},"total":{"type":["integer","null"],"title":"\u53d7\u6ce8\u5408\u8a08","description":"\u53d7\u6ce8\u5408\u8a08\u91d1\u984d\u3002\u8a08\u7b97\u5f0f: subtotal(\u5546\u54c1\u7a0e\u8fbc\u5408\u8a08) + deliveryFeeTotal(\u9001\u6599) + charge(\u624b\u6570\u6599) - discount(\u5024\u5f15\u304d)\u3002\u30ab\u30fc\u30c8\u306etotalPrice\u3068\u306f\u5225\u30d7\u30ed\u30d1\u30c6\u30a3 Fake\u89b3\u5bdf\u6570\u5024 12700\u301c12700; \u89b3\u5bdf\u5024 '12700'\u3002","example":12700,"minimum":0,"maximum":999999999},"orderStatus":{"title":"\u53d7\u6ce8\u30b9\u30c6\u30fc\u30bf\u30b9","description":"1=\u65b0\u898f\u53d7\u4ed8, 3=\u6ce8\u6587\u53d6\u6d88, 4=\u5bfe\u5fdc\u4e2d, 5=\u767a\u9001\u6e08\u307f, 6=\u5165\u91d1\u6e08\u307f, 7=\u6c7a\u6e08\u51e6\u7406\u4e2d, 8=\u8cfc\u5165\u51e6\u7406\u4e2d, 9=\u8fd4\u54c1\u3002Symfony Workflow\u30b9\u30c6\u30fc\u30c8\u30de\u30b7\u30f3\u3067\u9077\u79fb\u3092\u5236\u5fa1\u3002\u8a31\u53ef\u3055\u308c\u308b\u9077\u79fb: pay(1->6), packing(1,6->4), cancel(1,4,6->3), back_to_in_progress(3->4), ship(1,6,4->5), return(5->9), cancel_return(9->5)\u30027\u30688\u306fPurchaseFlow\u5185\u3067\u76f4\u63a5\u30bb\u30c3\u30c8\u3055\u308c\u30b9\u30c6\u30fc\u30c8\u30de\u30b7\u30f3\u9077\u79fb\u306e\u5bfe\u8c61\u5916 Fake\u89b3\u5bdf\u6570\u5024 1\u301c1; \u89b3\u5bdf\u5024 '1'\u3002","type":"integer","minimum":1,"maximum":9,"example":1}},"additionalProperties":false,"$comment":"\u914d\u5217\u8981\u7d20\u306fFake/Resource\u3067\u89b3\u5bdf\u3055\u308c\u305f\u65e2\u77e5property\u306b\u56fa\u5b9a\u3059\u308b\u3002\u65b0\u3057\u3044\u5217\u304c\u5fc5\u8981\u306b\u306a\u3063\u305f\u5834\u5408\u306fSemantic-Ex\u89b3\u5bdf\u306b\u8ffd\u52a0\u3057\u3066schema\u3092\u66f4\u65b0\u3059\u308b\u3002"},"minItems":0} |  |
| customerId | string|null | 会員ID - dtb_customer.id の不透明な文字列ハンドル。BeMart の Entity 層は数値ではなく文字列として保持する（マスアサインメント防止のため、Session/AuthZ 経由で読み出し、リクエスト本文からは受け取らない）。Favorite / Cart / Order の所有者キーとして横断使用 Fake観察文字長 12〜32; 観察値 'customer-001', '0123456789abcdef0123456789abcdef', 'customer-002', 'favorite-list-customer', 'favorite-html-customer', 'fedcba9876543210fedcba9876543210', 'aaaaaaaa00000000bbbbbbbb11111111', '10000000aaaa1111bbbb2222cccc3333'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} | customer-001 |
| orderCount | int|null | 注文件数 - /mypage/order-history のレスポンスで返す注文件数。一覧・集計・処理結果の規模を表す非負整数。 | Required | {"minimum":0,"maximum":2147483647} |  |

#### Links

| Relation | URL |
|----------|-----|
| goMypageHistory | [<code>page://self/mypage/history</code>](/mypage/history.md) |
| goMypage | [<code>page://self/mypage</code>](/mypage.md) |