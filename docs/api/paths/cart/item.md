<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /cart/item
EC-CUBE doAddCartItem —カートに商品を追加。

Resource is the HTTP entry point: it builds AddCartItemInput, hands it
to Becoming, and projects the resulting CartItemAdded into the response
body. Domain exceptions are mapped to HTTP codes per the integration
contract (see application-implement.md §DomainException → Code mapping).




## POST
Phase B Slice 9: all three params arrive from the HTTP request body
and are user-controlled. Declared as taint sources so Psalm can
trace them through Becoming into any downstream sink (Phase 2 will
surface real flows once Fake Reasons are swapped for DB-backed
implementations).

**ALPS**: `doAddCartItem`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| productCode | string | 商品コード（入力） - SKU/品番。在庫管理や受注明細での識別に使用 商品を識別するSKU。Fake corpusではASCII英数・ハイフン中心で、受注明細/カート明細の結合キーになる。 Fake観察文字長 10〜26。 |  | Required | {"minLength":0,"maxLength":64,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | sample-001 |
| quantity | int | 数量（入力） - 購入数量。カート明細と受注明細で共通使用 Fake観察数値 1〜3; 観察値 '1', '2', '3'。 |  | Optional | {"$comment":"\u6570\u91cf\uff08\u5165\u529b\uff09\u306f\u672c\u6765\u6570\u5024/\u5217\u6319\u306e\u696d\u52d9\u5024\u3060\u304c\u3001HTTP\u30d5\u30a9\u30fc\u30e0\u3067\u306f\u6587\u5b57\u5217\u3068\u3057\u3066\u5c4a\u304f\u3002Resource/Semantic\u5c64\u306e400\u5fdc\u7b54\u3092\u596a\u308f\u306a\u3044\u305f\u3081transport schema\u3067\u306f\u6587\u5b57\u5217\u5165\u529b\u3092\u8a31\u5bb9\u3059\u308b\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 1 |
| sessionPrefix | string | セッション接頭辞（入力） - 購入フローのカートキーを構成するセッションスコープの接頭辞。saleTypeId と組み合わせて販売種別ごとのカートを分離する。 Fake観察文字長 16〜23; 観察値 'session-prefix-1', 'session-checkout-pilot5'。 | session-prefix-1 | Optional | {"minLength":0,"maxLength":128,"default":"session-prefix-1","$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | session-prefix-1 |
| operation | string | 操作種別（入力） - /cart/item のunsafe操作結果を表す操作種別。成功時の差分、処理件数、冪等状態をクライアントに返す。 |  | Optional | {"minLength":0,"maxLength":64,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} |  |


### Response

[Object: POST /cart/item response](../schemas/post-cart-item.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| cartKey | string | カートキー - カート分離キー。形式: {セッションプレフィックス}_{販売種別ID}。EC-CUBEは販売種別ごとにカートを分離するため、異なる販売種別の商品は別カートになる 販売種別ごとにカートを分離するキー。ALPSのcartKeyはセッション接頭辞と販売種別IDから構成される。 Fake観察文字長 18〜23; 観察値 'session-prefix-1_1', 'session-prefix-1_2', 'session-checkout-pilot5'。 | Required | {"minLength":3,"maxLength":128,"pattern":"^.+_[0-9]+$"} | session-prefix-1_1 |
| requestedQuantity | int | 要求数量 - /cart/item の処理文脈から派生した要求数量。ALPS基礎語だけでは単位や用途が不足するため、このResource上の意味を明示する。 | Required | {"minimum":0,"maximum":2147483647} | 1 |
| saleTypeName | string|null | 販売種別 - 販売種別の名称。カート分離の基準となる。異なる販売種別の商品は別カート(cartKey)に分離される Fake観察文字長 4〜8; 観察値 '通常販売', '予約販売', 'ダウンロード販売'。 | Required | {"minLength":0,"maxLength":32} | 通常販売 |
| productCode | string | 商品コード - SKU/品番。在庫管理や受注明細での識別に使用 商品を識別するSKU。Fake corpusではASCII英数・ハイフン中心で、受注明細/カート明細の結合キーになる。 Fake観察文字長 10〜26。 | Required | {"minLength":0,"maxLength":64} | sample-001 |
| totalPrice | int | カート合計金額 - カート内の税込合計金額。PurchaseFlow.calculateTotal()で毎回再計算されるキャッシュ値。受注のtotalとは別プロパティ Fake観察数値 0〜3600; 観察値 '0', '1500', '3600'。 | Required | {"minimum":0,"maximum":999999999} | 0 |
| unitPrice | int | 単価（表示/計算用） - 明細1件あたりの単価。受注/カート明細・お気に入りスナップショットでは追加時点の price02 をスナップショットして保持する（後の値引きやマスタ改定に影響されない）。BeMart 側では `int` 円整数 Fake観察数値 1200〜9800; 観察値 '1200', '9800'。 | Required | {"minimum":0,"maximum":999999999} | 1200 |
| adjustedQuantity | int | 調整後数量 - /cart/item の処理文脈から派生した調整後数量。ALPS基礎語だけでは単位や用途が不足するため、このResource上の意味を明示する。 | Required | {"minimum":0,"maximum":2147483647} | 1 |
| deliveryFeeTotal | int|null | 送料合計 - 全配送先の送料合計（スナップショット）。deliveryFeeAmount（地域別送料）+ deliveryFee（商品別送料）×数量 の合計。DeliveryFeePreprocessorで計算。カートと受注の両方で使用 Fake観察数値 0〜800; 観察値 '600', '0', '500', '800', '700'。 | Required | {"minimum":0,"maximum":999999999} | 600 |

#### Links

| Relation | URL |
|----------|-----|
| goCart | [<code>page://self/cart</code>](/cart.md) |
| doUpdateCartItemQuantity | [<code>page://self/cart/item</code>](/cart/item.md) |
| doRemoveCartItem | [<code>page://self/cart/item</code>](/cart/item.md) |
| goCheckoutEntry | [<code>page://self/shopping</code>](/shopping.md) |
## PUT
EC-CUBE doUpdateCartItemQuantity — replace an item's quantity
(Pilot 10). Idempotent (PUT semantics), CSRF-guarded.

**ALPS**: `doUpdateCartItemQuantity`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| productCode | string | 商品コード（入力） - SKU/品番。在庫管理や受注明細での識別に使用 商品を識別するSKU。Fake corpusではASCII英数・ハイフン中心で、受注明細/カート明細の結合キーになる。 Fake観察文字長 10〜26。 |  | Required | {"minLength":0,"maxLength":64,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | sample-001 |
| quantity | int | 数量（入力） - 購入数量。カート明細と受注明細で共通使用 Fake観察数値 1〜3; 観察値 '1', '2', '3'。 |  | Required | {"$comment":"\u6570\u91cf\uff08\u5165\u529b\uff09\u306f\u672c\u6765\u6570\u5024/\u5217\u6319\u306e\u696d\u52d9\u5024\u3060\u304c\u3001HTTP\u30d5\u30a9\u30fc\u30e0\u3067\u306f\u6587\u5b57\u5217\u3068\u3057\u3066\u5c4a\u304f\u3002Resource/Semantic\u5c64\u306e400\u5fdc\u7b54\u3092\u596a\u308f\u306a\u3044\u305f\u3081transport schema\u3067\u306f\u6587\u5b57\u5217\u5165\u529b\u3092\u8a31\u5bb9\u3059\u308b\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 1 |
| sessionPrefix | string | セッション接頭辞（入力） - 購入フローのカートキーを構成するセッションスコープの接頭辞。saleTypeId と組み合わせて販売種別ごとのカートを分離する。 Fake観察文字長 16〜23; 観察値 'session-prefix-1', 'session-checkout-pilot5'。 | session-prefix-1 | Optional | {"minLength":0,"maxLength":128,"default":"session-prefix-1","$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | session-prefix-1 |


### Response

[Object: PUT /cart/item response](../schemas/put-cart-item.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| cartKey | string | カートキー - カート分離キー。形式: {セッションプレフィックス}_{販売種別ID}。EC-CUBEは販売種別ごとにカートを分離するため、異なる販売種別の商品は別カートになる 販売種別ごとにカートを分離するキー。ALPSのcartKeyはセッション接頭辞と販売種別IDから構成される。 Fake観察文字長 18〜23; 観察値 'session-prefix-1_1', 'session-prefix-1_2', 'session-checkout-pilot5'。 | Required | {"minLength":3,"maxLength":128,"pattern":"^.+_[0-9]+$"} | session-prefix-1_1 |
| requestedQuantity | int | 要求数量 - /cart/item の処理文脈から派生した要求数量。ALPS基礎語だけでは単位や用途が不足するため、このResource上の意味を明示する。 | Required | {"minimum":0,"maximum":2147483647} | 1 |
| saleTypeName | string|null | 販売種別 - 販売種別の名称。カート分離の基準となる。異なる販売種別の商品は別カート(cartKey)に分離される Fake観察文字長 4〜8; 観察値 '通常販売', '予約販売', 'ダウンロード販売'。 | Required | {"minLength":0,"maxLength":32} | 通常販売 |
| productCode | string | 商品コード - SKU/品番。在庫管理や受注明細での識別に使用 商品を識別するSKU。Fake corpusではASCII英数・ハイフン中心で、受注明細/カート明細の結合キーになる。 Fake観察文字長 10〜26。 | Required | {"minLength":0,"maxLength":64} | sample-001 |
| totalPrice | int | カート合計金額 - カート内の税込合計金額。PurchaseFlow.calculateTotal()で毎回再計算されるキャッシュ値。受注のtotalとは別プロパティ Fake観察数値 0〜3600; 観察値 '0', '1500', '3600'。 | Required | {"minimum":0,"maximum":999999999} | 0 |
| unitPrice | int | 単価（表示/計算用） - 明細1件あたりの単価。受注/カート明細・お気に入りスナップショットでは追加時点の price02 をスナップショットして保持する（後の値引きやマスタ改定に影響されない）。BeMart 側では `int` 円整数 Fake観察数値 1200〜9800; 観察値 '1200', '9800'。 | Required | {"minimum":0,"maximum":999999999} | 1200 |
| adjustedQuantity | int | 調整後数量 - /cart/item の処理文脈から派生した調整後数量。ALPS基礎語だけでは単位や用途が不足するため、このResource上の意味を明示する。 | Required | {"minimum":0,"maximum":2147483647} | 1 |
| deliveryFeeTotal | int|null | 送料合計 - 全配送先の送料合計（スナップショット）。deliveryFeeAmount（地域別送料）+ deliveryFee（商品別送料）×数量 の合計。DeliveryFeePreprocessorで計算。カートと受注の両方で使用 Fake観察数値 0〜800; 観察値 '600', '0', '500', '800', '700'。 | Required | {"minimum":0,"maximum":999999999} | 600 |

#### Links

| Relation | URL |
|----------|-----|
| goCart | [<code>page://self/cart</code>](/cart.md) |
## DELETE
EC-CUBE doRemoveCartItem — remove an item from the cart (Pilot 11).

Idempotent (DELETE), CSRF-guarded.

**ALPS**: `doRemoveCartItem`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| productCode | string | 商品コード（入力） - SKU/品番。在庫管理や受注明細での識別に使用 商品を識別するSKU。Fake corpusではASCII英数・ハイフン中心で、受注明細/カート明細の結合キーになる。 Fake観察文字長 10〜26。 |  | Required | {"minLength":0,"maxLength":64,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | sample-001 |
| sessionPrefix | string | セッション接頭辞（入力） - 購入フローのカートキーを構成するセッションスコープの接頭辞。saleTypeId と組み合わせて販売種別ごとのカートを分離する。 Fake観察文字長 16〜23; 観察値 'session-prefix-1', 'session-checkout-pilot5'。 | session-prefix-1 | Optional | {"minLength":0,"maxLength":128,"default":"session-prefix-1","$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | session-prefix-1 |


### Response

[Object: DELETE /cart/item response](../schemas/delete-cart-item.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| cartKey | string | カートキー - カート分離キー。形式: {セッションプレフィックス}_{販売種別ID}。EC-CUBEは販売種別ごとにカートを分離するため、異なる販売種別の商品は別カートになる 販売種別ごとにカートを分離するキー。ALPSのcartKeyはセッション接頭辞と販売種別IDから構成される。 Fake観察文字長 18〜23; 観察値 'session-prefix-1_1', 'session-prefix-1_2', 'session-checkout-pilot5'。 | Required | {"minLength":3,"maxLength":128,"pattern":"^.+_[0-9]+$"} | session-prefix-1_1 |
| productCode | string | 商品コード - SKU/品番。在庫管理や受注明細での識別に使用 商品を識別するSKU。Fake corpusではASCII英数・ハイフン中心で、受注明細/カート明細の結合キーになる。 Fake観察文字長 10〜26。 | Required | {"minLength":0,"maxLength":64} | sample-001 |
| totalPrice | int | カート合計金額 - カート内の税込合計金額。PurchaseFlow.calculateTotal()で毎回再計算されるキャッシュ値。受注のtotalとは別プロパティ Fake観察数値 0〜3600; 観察値 '0', '1500', '3600'。 | Required | {"minimum":0,"maximum":999999999} | 0 |
| deliveryFeeTotal | int|null | 送料合計 - 全配送先の送料合計（スナップショット）。deliveryFeeAmount（地域別送料）+ deliveryFee（商品別送料）×数量 の合計。DeliveryFeePreprocessorで計算。カートと受注の両方で使用 Fake観察数値 0〜800; 観察値 '600', '0', '500', '800', '700'。 | Required | {"minimum":0,"maximum":999999999} | 600 |

#### Links

| Relation | URL |
|----------|-----|
| goCart | [<code>page://self/cart</code>](/cart.md) |