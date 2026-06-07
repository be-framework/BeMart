<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /mypage/favorite
EC-CUBE doAddFavorite — お気に入りに追加 (Pilot 13).

AUTHZ via Session (customerId never in body). Idempotent re-add
returns 200 (alreadyExisted=true) rather than 201, so the UI can
distinguish first-add from re-add.




## POST
ALPS `doAddFavorite` に対応する POST 操作。

**ALPS**: `doAddFavorite`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| productCode | string | 商品コード（入力） - SKU/品番。在庫管理や受注明細での識別に使用 商品を識別するSKU。Fake corpusではASCII英数・ハイフン中心で、受注明細/カート明細の結合キーになる。 Fake観察文字長 10〜26。 |  | Required | {"minLength":0,"maxLength":64,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | sample-001 |


### Response

[Object: POST /mypage/favorite response](../schemas/post-mypage-favorite.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| alreadyExisted | boolean|null | 既存在フラグ - /mypage/favorite の処理状態を示す既存在フラグ。画面表示や冪等処理結果の分岐に使う真偽値。 | Required |  |  |
| productName | string|null | 商品名 - 商品の表示名 Fake観察文字長 6〜17。 | Required | {"minLength":0,"maxLength":128} | サンプル商品 A |
| productCode | string | 商品コード - SKU/品番。在庫管理や受注明細での識別に使用 商品を識別するSKU。Fake corpusではASCII英数・ハイフン中心で、受注明細/カート明細の結合キーになる。 Fake観察文字長 10〜26。 | Required | {"minLength":0,"maxLength":64} | sample-001 |
| unitPrice | int | 単価（表示/計算用） - 明細1件あたりの単価。受注/カート明細・お気に入りスナップショットでは追加時点の price02 をスナップショットして保持する（後の値引きやマスタ改定に影響されない）。BeMart 側では `int` 円整数 Fake観察数値 1200〜9800; 観察値 '1200', '9800'。 | Required | {"minimum":0,"maximum":999999999} | 1200 |
| customerId | string|null | 会員ID - dtb_customer.id の不透明な文字列ハンドル。BeMart の Entity 層は数値ではなく文字列として保持する（マスアサインメント防止のため、Session/AuthZ 経由で読み出し、リクエスト本文からは受け取らない）。Favorite / Cart / Order の所有者キーとして横断使用 Fake観察文字長 12〜32; 観察値 'customer-001', '0123456789abcdef0123456789abcdef', 'customer-002', 'favorite-list-customer', 'favorite-html-customer', 'fedcba9876543210fedcba9876543210', 'aaaaaaaa00000000bbbbbbbb11111111', '10000000aaaa1111bbbb2222cccc3333'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} | customer-001 |

#### Links

| Relation | URL |
|----------|-----|
| doRemoveFavorite | [<code>page://self/mypage/favorite</code>](/mypage/favorite.md) |
| goProduct | [<code>page://self/product</code>](/product.md) |
## DELETE
EC-CUBE doRemoveFavorite — お気に入りから削除 (idempotent inverse
of Pilot 13). DELETE is idempotent (ALPS type=idempotent):
re-removing an already-absent item returns 200 with
alreadyAbsent=true rather than 404. The flag lets the UI
distinguish first-remove from re-remove without leaking the
underlying state.

Unlike onPost, we do NOT validate that productCode resolves to
a real product — DELETE removes a stored row, not a product.

**ALPS**: `doRemoveFavorite`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| productCode | string | 商品コード（入力） - SKU/品番。在庫管理や受注明細での識別に使用 商品を識別するSKU。Fake corpusではASCII英数・ハイフン中心で、受注明細/カート明細の結合キーになる。 Fake観察文字長 10〜26。 |  | Required | {"minLength":0,"maxLength":64,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | sample-001 |


### Response

[Object: DELETE /mypage/favorite response](../schemas/delete-mypage-favorite.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| alreadyAbsent | boolean|null | 既不存在フラグ - /mypage/favorite の処理状態を示す既不存在フラグ。画面表示や冪等処理結果の分岐に使う真偽値。 | Required |  |  |
| customerId | string|null | 会員ID - dtb_customer.id の不透明な文字列ハンドル。BeMart の Entity 層は数値ではなく文字列として保持する（マスアサインメント防止のため、Session/AuthZ 経由で読み出し、リクエスト本文からは受け取らない）。Favorite / Cart / Order の所有者キーとして横断使用 Fake観察文字長 12〜32; 観察値 'customer-001', '0123456789abcdef0123456789abcdef', 'customer-002', 'favorite-list-customer', 'favorite-html-customer', 'fedcba9876543210fedcba9876543210', 'aaaaaaaa00000000bbbbbbbb11111111', '10000000aaaa1111bbbb2222cccc3333'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} | customer-001 |
| productCode | string | 商品コード - SKU/品番。在庫管理や受注明細での識別に使用 商品を識別するSKU。Fake corpusではASCII英数・ハイフン中心で、受注明細/カート明細の結合キーになる。 Fake観察文字長 10〜26。 | Required | {"minLength":0,"maxLength":64} | sample-001 |

#### Links

| Relation | URL |
|----------|-----|
| goMypageWithdraw | [<code>page://self/mypage/withdraw</code>](/mypage/withdraw.md) |
| goMypage | [<code>page://self/mypage</code>](/mypage.md) |