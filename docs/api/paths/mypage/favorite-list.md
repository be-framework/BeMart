<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /mypage/favorite-list
EC-CUBE goFavoriteList — お気に入り一覧 (read pair for Pilot 13's
doAddFavorite + doRemoveFavorite).

Safe read. No CSRF (read-only). AUTHN is enforced in the Be layer:
the customer can only see their own favorites — the customerId
comes from CustomerSession, never the request body (Pilot 5 F-2
lesson).

Failure mapping:
  - SemanticVariableException  → 400 (defensive — the Input is 0-arg)
  - UnauthenticatedException   → 401 (no session)




## GET
ALPS `goFavoriteList` に対応する GET 操作。

**ALPS**: `goFavoriteList` - お気に入り一覧を見る



### Request

_No parameters required_

### Response

[Object: GET /mypage/favorite-list response](../schemas/get-mypage-favorite-list.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| favoriteCount | int|null | お気に入り件数 - /mypage/favorite-list のレスポンスで返すお気に入り件数。一覧・集計・処理結果の規模を表す非負整数。 | Required | {"minimum":0,"maximum":2147483647} |  |
| customerId | string|null | 会員ID - dtb_customer.id の不透明な文字列ハンドル。BeMart の Entity 層は数値ではなく文字列として保持する（マスアサインメント防止のため、Session/AuthZ 経由で読み出し、リクエスト本文からは受け取らない）。Favorite / Cart / Order の所有者キーとして横断使用 Fake観察文字長 12〜32; 観察値 'customer-001', '0123456789abcdef0123456789abcdef', 'customer-002', 'favorite-list-customer', 'favorite-html-customer', 'fedcba9876543210fedcba9876543210', 'aaaaaaaa00000000bbbbbbbb11111111', '10000000aaaa1111bbbb2222cccc3333'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} | customer-001 |
| favorites | array|null | お気に入り商品一覧 - /mypage/favorite-list のレスポンスで扱うお気に入り商品一覧。配列要素はALPS意味とFake観察に基づき、固定できない動的列は例外理由を台帳化する。 | Required | {"items":{"type":"object","title":"\u304a\u6c17\u306b\u5165\u308a\u5546\u54c1","description":"/mypage/favorite-list \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u304a\u6c17\u306b\u5165\u308a\u5546\u54c1\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `favorites` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002","properties":{"productCode":{"title":"\u5546\u54c1\u30b3\u30fc\u30c9","description":"SKU/\u54c1\u756a\u3002\u5728\u5eab\u7ba1\u7406\u3084\u53d7\u6ce8\u660e\u7d30\u3067\u306e\u8b58\u5225\u306b\u4f7f\u7528 \u5546\u54c1\u3092\u8b58\u5225\u3059\u308bSKU\u3002Fake corpus\u3067\u306fASCII\u82f1\u6570\u30fb\u30cf\u30a4\u30d5\u30f3\u4e2d\u5fc3\u3067\u3001\u53d7\u6ce8\u660e\u7d30/\u30ab\u30fc\u30c8\u660e\u7d30\u306e\u7d50\u5408\u30ad\u30fc\u306b\u306a\u308b\u3002 Fake\u89b3\u5bdf\u6587\u5b57\u9577 10\u301c26\u3002","type":["string","null"],"minLength":0,"maxLength":64,"example":"sample-001"},"name":{"type":["string","null"],"minLength":0,"maxLength":32,"title":"\u51e6\u7406\u8868\u793a\u540d","description":"Fake\u89b3\u5bdf\u6587\u5b57\u9577 1\u301c7; \u89b3\u5bdf\u5024 '\u30c6\u30b9\u30c8\u7ba1\u7406\u8005', '\u526f\u7ba1\u7406\u8005', '\u5e97\u8217\u30aa\u30fc\u30ca\u30fc', '\u524a\u9664\u6e08\u307f\u7ba1\u7406\u8005', 'Red', 'Blue', 'S', 'Color'\u3002","example":"\u30c6\u30b9\u30c8\u7ba1\u7406\u8005"},"productName":{"type":["string","null"],"minLength":0,"maxLength":128,"title":"\u5546\u54c1\u540d","description":"\u5546\u54c1\u306e\u8868\u793a\u540d Fake\u89b3\u5bdf\u6587\u5b57\u9577 6\u301c17\u3002","example":"\u30b5\u30f3\u30d7\u30eb\u5546\u54c1 A"},"price02":{"title":"\u8ca9\u58f2\u4fa1\u683c","description":"\u5b9f\u969b\u306e\u8ca9\u58f2\u4fa1\u683c\uff08\u7a0e\u629c\uff09\u3002\u7a0e\u8a08\u7b97\u30fb\u5c0f\u8a08\u8a08\u7b97\u306e\u30d9\u30fc\u30b9 Fake\u89b3\u5bdf\u6570\u5024 800\u301c28000\u3002","type":["integer","null"],"minimum":0,"maximum":999999999,"example":3500},"mainImage":{"title":"\u30e1\u30a4\u30f3\u753b\u50cfURI","description":"/mypage/favorite-list \u306e\u753b\u9762\u8868\u793a\u306b\u4f7f\u3046\u30e1\u30a4\u30f3\u753b\u50cfURI\u3002\u696d\u52d9\u30a8\u30f3\u30c6\u30a3\u30c6\u30a3\u305d\u306e\u3082\u306e\u3067\u306f\u306a\u304f\u30c6\u30f3\u30d7\u30ec\u30fc\u30c8/\u4e00\u89a7\u8868\u793a\u306e\u88dc\u52a9\u5024\u3002","type":["string","null"],"format":"uri-reference","minLength":1,"maxLength":2048,"example":"/products"},"unitPrice":{"title":"\u5358\u4fa1\uff08\u8868\u793a/\u8a08\u7b97\u7528\uff09","description":"\u660e\u7d301\u4ef6\u3042\u305f\u308a\u306e\u5358\u4fa1\u3002\u53d7\u6ce8/\u30ab\u30fc\u30c8\u660e\u7d30\u30fb\u304a\u6c17\u306b\u5165\u308a\u30b9\u30ca\u30c3\u30d7\u30b7\u30e7\u30c3\u30c8\u3067\u306f\u8ffd\u52a0\u6642\u70b9\u306e price02 \u3092\u30b9\u30ca\u30c3\u30d7\u30b7\u30e7\u30c3\u30c8\u3057\u3066\u4fdd\u6301\u3059\u308b\uff08\u5f8c\u306e\u5024\u5f15\u304d\u3084\u30de\u30b9\u30bf\u6539\u5b9a\u306b\u5f71\u97ff\u3055\u308c\u306a\u3044\uff09\u3002BeMart \u5074\u3067\u306f `int` \u5186\u6574\u6570 Fake\u89b3\u5bdf\u6570\u5024 1200\u301c9800; \u89b3\u5bdf\u5024 '1200', '9800'\u3002","type":["integer","null"],"minimum":0,"maximum":999999999,"example":1200},"fileName":{"type":["string","null"],"minLength":1,"maxLength":255,"title":"\u30d5\u30a1\u30a4\u30eb\u540d","description":"\u5546\u54c1\u753b\u50cf\u306e\u30d5\u30a1\u30a4\u30eb\u540d Fake\u89b3\u5bdf\u6587\u5b57\u9577 12\u301c15; \u89b3\u5bdf\u5024 'Mail/order.twig', 'Mail/entry.twig', 'sample-a.jpg', 'sample-b.jpg'\u3002","example":"Mail/order.twig"}},"additionalProperties":false,"$comment":"\u914d\u5217\u8981\u7d20\u306fFake/Resource\u3067\u89b3\u5bdf\u3055\u308c\u305f\u65e2\u77e5property\u306b\u56fa\u5b9a\u3059\u308b\u3002\u65b0\u3057\u3044\u5217\u304c\u5fc5\u8981\u306b\u306a\u3063\u305f\u5834\u5408\u306fSemantic-Ex\u89b3\u5bdf\u306b\u8ffd\u52a0\u3057\u3066schema\u3092\u66f4\u65b0\u3059\u308b\u3002"},"minItems":0} |  |

#### Links

| Relation | URL |
|----------|-----|
| doAddFavorite | [<code>page://self/mypage/favorite</code>](/mypage/favorite.md) |
| doRemoveFavorite | [<code>page://self/mypage/favorite</code>](/mypage/favorite.md) |
| goMypage | [<code>page://self/mypage</code>](/mypage.md) |