<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /mypage
EC-CUBE goMypage — 会員マイページのダッシュボード.

Safe read. No CSRF (read-only). AUTHN required — Be Final raises
UnauthenticatedException when the session has no customerId, which
we map to 401. Aggregates basic profile + recent orders +
favorite count into a flat dashboard projection.

Failure mapping:
  - SemanticVariableException → 400 (parameter format invalid)
  - UnauthenticatedException  → 401 (no / stale session)

Coexists with `Resource\Page\Mypage\` namespace (Change, Favorite,
…) — PHP allows a file and a sibling directory of the same name to
share a namespace prefix.




## GET
ALPS `goMypage` に対応する GET 操作。

**ALPS**: `goMypage`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| orderLimit | int | 一覧制御項目（入力） - /mypage のレスポンスで返す一覧制御項目。一覧、集計、CSV処理結果の規模を表す非負の数値。 | 5 | Optional | {"default":5,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} |  |


### Response

[Object: GET /mypage response](../schemas/get-mypage.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| name01 | string|null | 姓 - 顧客・受注・配送先・お問い合わせで共通使用される姓 Fake観察文字長 2〜2; 観察値 '鈴木', '山田', '佐藤', '高橋', '退会'。 | Required | {"minLength":0,"maxLength":80} | 鈴木 |
| customerId | string|null | 会員ID - dtb_customer.id の不透明な文字列ハンドル。BeMart の Entity 層は数値ではなく文字列として保持する（マスアサインメント防止のため、Session/AuthZ 経由で読み出し、リクエスト本文からは受け取らない）。Favorite / Cart / Order の所有者キーとして横断使用 Fake観察文字長 12〜32; 観察値 'customer-001', '0123456789abcdef0123456789abcdef', 'customer-002', 'favorite-list-customer', 'favorite-html-customer', 'fedcba9876543210fedcba9876543210', 'aaaaaaaa00000000bbbbbbbb11111111', '10000000aaaa1111bbbb2222cccc3333'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} | customer-001 |
| recentOrders | array|null | 最近の注文一覧 - /mypage のレスポンスで扱う最近の注文一覧。配列要素はALPS意味とFake観察に基づき、固定できない動的列は例外理由を台帳化する。 | Required | {"items":{"type":"object","title":"\u6700\u8fd1\u306e\u6ce8\u6587","description":"/mypage \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u6700\u8fd1\u306e\u6ce8\u6587\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `recentOrders` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002","properties":{"orderNo":{"type":["string","null"],"minLength":0,"maxLength":64,"title":"\u6ce8\u6587\u756a\u53f7","description":"\u9867\u5ba2\u5411\u3051\u306e\u6ce8\u6587\u756a\u53f7\u3002\u30d5\u30a9\u30fc\u30de\u30c3\u30c8\u306f\u30ab\u30b9\u30bf\u30de\u30a4\u30ba\u53ef\u80fd Fake\u89b3\u5bdf\u6587\u5b57\u9577 32\u301c32; \u89b3\u5bdf\u5024 'past0000000000000000000000000001'\u3002","example":"past0000000000000000000000000001"},"orderStatus":{"title":"\u53d7\u6ce8\u30b9\u30c6\u30fc\u30bf\u30b9","description":"1=\u65b0\u898f\u53d7\u4ed8, 3=\u6ce8\u6587\u53d6\u6d88, 4=\u5bfe\u5fdc\u4e2d, 5=\u767a\u9001\u6e08\u307f, 6=\u5165\u91d1\u6e08\u307f, 7=\u6c7a\u6e08\u51e6\u7406\u4e2d, 8=\u8cfc\u5165\u51e6\u7406\u4e2d, 9=\u8fd4\u54c1\u3002Symfony Workflow\u30b9\u30c6\u30fc\u30c8\u30de\u30b7\u30f3\u3067\u9077\u79fb\u3092\u5236\u5fa1\u3002\u8a31\u53ef\u3055\u308c\u308b\u9077\u79fb: pay(1->6), packing(1,6->4), cancel(1,4,6->3), back_to_in_progress(3->4), ship(1,6,4->5), return(5->9), cancel_return(9->5)\u30027\u30688\u306fPurchaseFlow\u5185\u3067\u76f4\u63a5\u30bb\u30c3\u30c8\u3055\u308c\u30b9\u30c6\u30fc\u30c8\u30de\u30b7\u30f3\u9077\u79fb\u306e\u5bfe\u8c61\u5916 Fake\u89b3\u5bdf\u6570\u5024 1\u301c1; \u89b3\u5bdf\u5024 '1'\u3002","type":["integer","null"],"minimum":1,"maximum":9,"example":1},"orderDate":{"title":"\u6ce8\u6587\u65e5","description":"\u6ce8\u6587\u78ba\u5b9a\u65e5\u6642 Fake\u89b3\u5bdf\u6587\u5b57\u9577 19\u301c19; \u89b3\u5bdf\u5024 '2026-04-01 10:00:00'\u3002","type":["string","null"],"example":"2026-04-01 10:00:00","pattern":"^\\d{4}-\\d{2}-\\d{2}([ T]\\d{2}:\\d{2}:\\d{2}([+-]\\d{2}:?\\d{2}|Z)?)?$"},"paymentTotal":{"type":["integer","null"],"title":"\u652f\u6255\u5408\u8a08","description":"\u5b9f\u969b\u306e\u652f\u6255\u91d1\u984d\u3002\u521d\u671f\u5024\u306ftotal\u3068\u540c\u5024\u3067\u3001PointProcessor\u304c\u30dd\u30a4\u30f3\u30c8\u5024\u5f15\u304d\u306eOrderItem\uff08type=POINT_DISCOUNT\u3001\u4e0d\u8ab2\u7a0e\uff09\u3092\u8ffd\u52a0\u5f8c\u306bPurchaseFlow.calculateTotal()\u3067\u518d\u8a08\u7b97\u3055\u308c\u308b\u3002\u8a08\u7b97\u5f0f: total - (\u5229\u7528\u30dd\u30a4\u30f3\u30c8 x pointConversionRate) Fake\u89b3\u5bdf\u6570\u5024 12700\u301c12700; \u89b3\u5bdf\u5024 '12700'\u3002","example":12700,"minimum":0,"maximum":999999999},"total":{"type":["integer","null"],"title":"\u53d7\u6ce8\u5408\u8a08","description":"\u53d7\u6ce8\u5408\u8a08\u91d1\u984d\u3002\u8a08\u7b97\u5f0f: subtotal(\u5546\u54c1\u7a0e\u8fbc\u5408\u8a08) + deliveryFeeTotal(\u9001\u6599) + charge(\u624b\u6570\u6599) - discount(\u5024\u5f15\u304d)\u3002\u30ab\u30fc\u30c8\u306etotalPrice\u3068\u306f\u5225\u30d7\u30ed\u30d1\u30c6\u30a3 Fake\u89b3\u5bdf\u6570\u5024 12700\u301c12700; \u89b3\u5bdf\u5024 '12700'\u3002","example":12700,"minimum":0,"maximum":999999999},"itemCount":{"type":["integer","null"],"minimum":0,"maximum":10000,"title":"\u660e\u7d30\u4ef6\u6570","description":"/mypage \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u3067\u8fd4\u3059\u660e\u7d30\u4ef6\u6570\u3002\u4e00\u89a7\u30fb\u96c6\u8a08\u30fb\u51e6\u7406\u7d50\u679c\u306e\u898f\u6a21\u3092\u8868\u3059\u975e\u8ca0\u6574\u6570\u3002","example":1},"items":{"type":["array","null"],"title":"\u6ce8\u6587\u5546\u54c1\u4e00\u89a7","description":"/mypage \u306e\u6700\u8fd1\u306e\u6ce8\u6587\u3067\u8868\u793a\u3059\u308b\u5546\u54c1\u660e\u7d30\u30b9\u30ca\u30c3\u30d7\u30b7\u30e7\u30c3\u30c8\u3002\u6ce8\u6587\u5c65\u6b74\u8a73\u7d30\u3068\u540c\u3058\u304f\u3001\u6ce8\u6587\u6642\u70b9\u306e\u5546\u54c1\u30b3\u30fc\u30c9\u30fb\u5546\u54c1\u540d\u30fb\u6570\u91cf\u30fb\u5358\u4fa1\u3092\u8fd4\u3059\u3002","items":{"type":"object","title":"\u6ce8\u6587\u5546\u54c1","properties":{"productCode":{"$ref":"#/$defs/productCode"},"productName":{"type":"string","minLength":0,"maxLength":128,"title":"\u5546\u54c1\u540d","description":"\u5546\u54c1\u306e\u8868\u793a\u540d\u3002\u6ce8\u6587\u6642\u70b9\u306e\u660e\u7d30\u30b9\u30ca\u30c3\u30d7\u30b7\u30e7\u30c3\u30c8\u3068\u3057\u3066\u4fdd\u6301\u3059\u308b\u3002","example":"\u30b5\u30f3\u30d7\u30eb\u5546\u54c1 A"},"quantity":{"$ref":"#/$defs/quantity"},"unitPrice":{"$ref":"#/$defs/price"}},"required":["productCode","productName","quantity","unitPrice"],"additionalProperties":false},"minItems":0}},"additionalProperties":false,"$comment":"\u914d\u5217\u8981\u7d20\u306fFake/Resource\u3067\u89b3\u5bdf\u3055\u308c\u305f\u65e2\u77e5property\u306b\u56fa\u5b9a\u3059\u308b\u3002\u65b0\u3057\u3044\u5217\u304c\u5fc5\u8981\u306b\u306a\u3063\u305f\u5834\u5408\u306fSemantic-Ex\u89b3\u5bdf\u306b\u8ffd\u52a0\u3057\u3066schema\u3092\u66f4\u65b0\u3059\u308b\u3002"},"minItems":0} |  |
| email | string | メールアドレス - 会員のログインIDを兼ねる。有効会員間で一意 ログインIDを兼ねるメールアドレス。会員登録・ログイン・通知で共通に使う。 Fake観察文字長 15〜58; 観察値 'bob@example.com', 'login-test@example.com', 'alice@example.com', 'carol@example.com', 'provisional@example.com', 'withdrawn-30000000aaaa3333bbbb4444cccc5555@example.invalid'。 | Required | {"format":"email","minLength":3,"maxLength":254} | alice@example.com |
| favoriteCount | int|null | お気に入り件数 - /mypage のレスポンスで返すお気に入り件数。一覧・集計・処理結果の規模を表す非負整数。 | Required | {"minimum":0,"maximum":2147483647} |  |
| recentOrderCount | int|null | 最近の注文件数 - /mypage のレスポンスで返す最近の注文件数。一覧・集計・処理結果の規模を表す非負整数。 | Required | {"minimum":0,"maximum":2147483647} |  |
| name02 | string|null | 名 - 顧客・受注・配送先・お問い合わせで共通使用される名 Fake観察文字長 1〜3; 観察値 'アリス', '太郎', '次郎', '花子', '三郎', '済'。 | Required | {"minLength":0,"maxLength":80} | アリス |

#### Links

| Relation | URL |
|----------|-----|
| goOrderHistory | [<code>page://self/mypage/order-history</code>](/mypage/order-history.md) |
| goMypageHistory | [<code>page://self/mypage/history</code>](/mypage/history.md) |
| goMypageChange | [<code>page://self/mypage/change</code>](/mypage/change.md) |
| goCustomerAddressList | [<code>page://self/mypage/address-list</code>](/mypage/address-list.md) |
| goFavoriteList | [<code>page://self/mypage/favorite-list</code>](/mypage/favorite-list.md) |
| goMypageWithdraw | [<code>page://self/mypage/withdraw</code>](/mypage/withdraw.md) |
| goCart | [<code>page://self/cart</code>](/cart.md) |
| goProductList | [<code>page://self/products</code>](/products.md) |
| doAddFavorite | [<code>page://self/mypage/favorite</code>](/mypage/favorite.md) |
| doRemoveFavorite | [<code>page://self/mypage/favorite</code>](/mypage/favorite.md) |