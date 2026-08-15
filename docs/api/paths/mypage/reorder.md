<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /mypage/reorder
EC-CUBE doReorder — 再注文 (Mypage/Reorder, Pilot 12).

Repopulates the current customer's cart(s) from a past order.
ALPS: "在庫切れ商品はスキップ、現在価格を適用" — out-of-stock /
discontinued products are skipped, current prices apply.

Failure mapping:
  - SemanticVariableException           → 400 (orderNo malformed)
  - UnauthenticatedException            → 401 (no logged-in customer)
  - UnauthorizedOrderAccessException    → 403 (not the order owner)
  - OrderNotFoundException              → 404 (no such order)
  - CSRF                                → 403 (checked before AUTHN)




## POST
ALPS `doReorder` に対応する POST 操作。

**ALPS**: `doReorder` - 再注文する



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| orderNo | string | 注文番号（入力） - 顧客向けの注文番号。フォーマットはカスタマイズ可能 Fake観察文字長 32〜32; 観察値 'past0000000000000000000000000001'。 |  | Required | {"minLength":0,"maxLength":64,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | past0000000000000000000000000001 |


### Response

[Object: POST /mypage/reorder response](../schemas/post-mypage-reorder.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| skippedCount | int|null | 件数 - /mypage/reorder のレスポンスで返す件数。一覧・集計・処理結果の規模を表す非負整数。 | Required | {"minimum":0,"maximum":2147483647} |  |
| skippedProductCodes | array|null | 処理識別子 - /mypage/reorder のレスポンスで扱う処理識別子。ALPS、Fake観察、Resource境界の形状から導いた業務契約。 | Required | {"items":{"title":"\u5546\u54c1\u30b3\u30fc\u30c9","description":"SKU/\u54c1\u756a\u3002\u5728\u5eab\u7ba1\u7406\u3084\u53d7\u6ce8\u660e\u7d30\u3067\u306e\u8b58\u5225\u306b\u4f7f\u7528 SKU\u3068\u3057\u3066\u5728\u5eab\u30fb\u30ab\u30fc\u30c8\u30fb\u53d7\u6ce8\u660e\u7d30\u3092\u63a5\u7d9a\u3059\u308b\u3002Fake\u89b3\u5bdf\u3067\u306fASCII\u82f1\u6570\u3068\u30cf\u30a4\u30d5\u30f3\u4e2d\u5fc3\u3002","type":"string","minLength":1,"maxLength":64,"pattern":"^[A-Za-z0-9][A-Za-z0-9._-]{0,63}$","example":"sample-001"},"minItems":0} |  |
| addedCount | int|null | 件数 - /mypage/reorder のレスポンスで返す件数。一覧・集計・処理結果の規模を表す非負整数。 | Required | {"minimum":0,"maximum":2147483647} |  |
| cartKeys | array|null | 処理一覧 - /mypage/reorder のレスポンスで扱う処理一覧。ALPS、Fake観察、Resource境界の形状から導いた業務契約。 | Required | {"items":{"title":"\u30ab\u30fc\u30c8\u30ad\u30fc","description":"\u30ab\u30fc\u30c8\u5206\u96e2\u30ad\u30fc\u3002\u5f62\u5f0f: {\u30bb\u30c3\u30b7\u30e7\u30f3\u30d7\u30ec\u30d5\u30a3\u30c3\u30af\u30b9}_{\u8ca9\u58f2\u7a2e\u5225ID}\u3002EC-CUBE\u306f\u8ca9\u58f2\u7a2e\u5225\u3054\u3068\u306b\u30ab\u30fc\u30c8\u3092\u5206\u96e2\u3059\u308b\u305f\u3081\u3001\u7570\u306a\u308b\u8ca9\u58f2\u7a2e\u5225\u306e\u5546\u54c1\u306f\u5225\u30ab\u30fc\u30c8\u306b\u306a\u308b","type":"string","minLength":3,"maxLength":128,"pattern":"^.+_[0-9]+$","example":"session-prefix-1_1"},"minItems":0} |  |
| orderNo | string|null | 注文番号 - 顧客向けの注文番号。フォーマットはカスタマイズ可能 Fake観察文字長 32〜32; 観察値 'past0000000000000000000000000001'。 | Required | {"minLength":0,"maxLength":64} | past0000000000000000000000000001 |
| customerId | string|null | 会員ID - dtb_customer.id の不透明な文字列ハンドル。BeMart の Entity 層は数値ではなく文字列として保持する（マスアサインメント防止のため、Session/AuthZ 経由で読み出し、リクエスト本文からは受け取らない）。Favorite / Cart / Order の所有者キーとして横断使用 Fake観察文字長 12〜32; 観察値 'customer-001', '0123456789abcdef0123456789abcdef', 'customer-002', 'favorite-list-customer', 'favorite-html-customer', 'fedcba9876543210fedcba9876543210', 'aaaaaaaa00000000bbbbbbbb11111111', '10000000aaaa1111bbbb2222cccc3333'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} | customer-001 |

#### Links

| Relation | URL |
|----------|-----|
| goCart | [<code>page://self/cart</code>](/cart.md) |