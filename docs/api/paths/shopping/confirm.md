<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /shopping/confirm
EC-CUBE goShoppingConfirm — 注文内容のご確認.

The order-review screen the customer confirms before `doCheckout`.
EC-CUBE's checkout flow runs `doConfirmOrder` → `ShoppingConfirm`
(ALPS `#ShoppingConfirm`) between `goShopping` and `doCheckout`.

Phase 3 enrichment — this resource now drives the `doConfirmOrder` Be
Becoming chain ({@see \ConfirmOrderInput} → … → {@see \OrderConfirmed})
rather than being a thin pure renderer. The chain resolves the
processing pre-order, runs the PurchaseFlow totals, verifies payment
and branches; on success the body carries the full confirm-screen
projection EC-CUBE's `Shopping/confirm.twig` renders — the customer
info, the order's line items, the payment method and the
tax-inclusive totals.

On a verify failure the chain produces an {@see \OrderConfirmFailed}
Final; the resource forwards the customer to the ShoppingError state
(`goShoppingError`), mirroring EC-CUBE's controller behaviour.

Failure mapping mirrors {@see \Checkout}, the other consumer of the same
pre-order ownership rule:
  - PreOrderNotFoundException           → 404 (the pre-order never existed)
  - UnauthorizedPreOrderAccessException → 403 (not the owner)
  - SemanticVariableException           → 400 (preOrderId malformed)

Maps to `page://self/shopping/confirm`. The submit target is
doCheckout (`page://self/shopping/checkout`).




## GET
ALPS `goShopping` に対応する GET 操作。

**ALPS**: `goShopping`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| preOrderId | string | 仮注文ID（入力） - 購入フローの一時セッショントークン（SHA1ハッシュ）。カートと受注を紐づける。予約注文（pre-order）IDではない。チェックアウト開始時に生成、注文確定またはカート破棄で消去 Fake観察文字長 40〜40; 観察値 'deadbeefcafe1234567890abcdef01234567890a', 'deadbeefcafe1234567890abcdef01234567890b', 'aaaa00000000000000000000000000000000aaaa', 'past00000000000000000000000000000000past', 'deadbeefcafe1234567890abcdef01234567890c', 'bbbb00000000000000000000000000000000bbbb', 'cccc00000000000000000000000000000000cccc', 'aceface0000000000000000000000000000a11ce'。 | aceface0000000000000000000000000000a11ce | Optional | {"default":"aceface0000000000000000000000000000a11ce","minLength":0,"maxLength":128,"$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | deadbeefcafe1234567890abcdef01234567890a |
| paymentMethodId | int | 支払方法ID - 受注に紐づく支払方法マスタID。Fake/EC-CUBE境界ではDB採番値として扱う。 | 2 | Optional | {"default":2,"minLength":0,"maxLength":128,"$comment":"\u652f\u6255\u65b9\u6cd5ID\u306f\u696d\u52d9\u4e0aID\u3060\u304c\u3001HTTP\u30d5\u30a9\u30fc\u30e0\u3067\u306f\u6587\u5b57\u5217\u3068\u3057\u3066\u5c4a\u304f\u3002Resource/Semantic\u5c64\u306e\u691c\u8a3c\u3092\u901a\u3059\u305f\u3081transport schema\u3067\u306fstring|integer\u3092\u8a31\u5bb9\u3059\u308b\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 2 |


### Response

[Object: GET /shopping/confirm response](../schemas/get-shopping-confirm.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| addPoint | int|null | 付与ポイント - 注文により付与されるポイント数。商品単価(税抜) x pointRate x 数量で明細ごとに計算し合算。利用ポイント分を控除。発送済み(DELIVERED)遷移時に会員のpointに加算 Fake観察数値 0〜127; 観察値 '127', '0'。 | Required | {"minimum":0,"maximum":2147483647} | 127 |
| items | array|null | 明細一覧 - /shopping/confirm の親オブジェクト `` に含まれる明細配列。商品・カート・受注明細の文脈で解釈する。 | Required | {"items":{"type":["object","null"],"title":"\u660e\u7d30","description":"/shopping/confirm \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u660e\u7d30\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `items` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002","properties":{"productName":{"type":["string","null"],"minLength":0,"maxLength":128,"title":"\u5546\u54c1\u540d","description":"\u5546\u54c1\u306e\u8868\u793a\u540d Fake\u89b3\u5bdf\u6587\u5b57\u9577 6\u301c17\u3002","example":"\u30b5\u30f3\u30d7\u30eb\u5546\u54c1 A"},"productCode":{"title":"\u5546\u54c1\u30b3\u30fc\u30c9","description":"SKU/\u54c1\u756a\u3002\u5728\u5eab\u7ba1\u7406\u3084\u53d7\u6ce8\u660e\u7d30\u3067\u306e\u8b58\u5225\u306b\u4f7f\u7528 \u5546\u54c1\u3092\u8b58\u5225\u3059\u308bSKU\u3002Fake corpus\u3067\u306fASCII\u82f1\u6570\u30fb\u30cf\u30a4\u30d5\u30f3\u4e2d\u5fc3\u3067\u3001\u53d7\u6ce8\u660e\u7d30/\u30ab\u30fc\u30c8\u660e\u7d30\u306e\u7d50\u5408\u30ad\u30fc\u306b\u306a\u308b\u3002 Fake\u89b3\u5bdf\u6587\u5b57\u9577 10\u301c26\u3002","type":"string","minLength":0,"maxLength":64,"example":"sample-001"},"quantity":{"title":"\u6570\u91cf","description":"\u8cfc\u5165\u6570\u91cf\u3002\u30ab\u30fc\u30c8\u660e\u7d30\u3068\u53d7\u6ce8\u660e\u7d30\u3067\u5171\u901a\u4f7f\u7528 Fake\u89b3\u5bdf\u6570\u5024 1\u301c3; \u89b3\u5bdf\u5024 '1', '2', '3'\u3002","type":"integer","minimum":1,"maximum":999,"example":1},"totalPrice":{"title":"\u30ab\u30fc\u30c8\u5408\u8a08\u91d1\u984d","description":"\u30ab\u30fc\u30c8\u5185\u306e\u7a0e\u8fbc\u5408\u8a08\u91d1\u984d\u3002PurchaseFlow.calculateTotal()\u3067\u6bce\u56de\u518d\u8a08\u7b97\u3055\u308c\u308b\u30ad\u30e3\u30c3\u30b7\u30e5\u5024\u3002\u53d7\u6ce8\u306etotal\u3068\u306f\u5225\u30d7\u30ed\u30d1\u30c6\u30a3 Fake\u89b3\u5bdf\u6570\u5024 0\u301c3600; \u89b3\u5bdf\u5024 '0', '1500', '3600'\u3002","type":"integer","minimum":0,"maximum":999999999,"example":0},"unitPrice":{"title":"\u5358\u4fa1\uff08\u8868\u793a/\u8a08\u7b97\u7528\uff09","description":"\u660e\u7d301\u4ef6\u3042\u305f\u308a\u306e\u5358\u4fa1\u3002\u53d7\u6ce8/\u30ab\u30fc\u30c8\u660e\u7d30\u30fb\u304a\u6c17\u306b\u5165\u308a\u30b9\u30ca\u30c3\u30d7\u30b7\u30e7\u30c3\u30c8\u3067\u306f\u8ffd\u52a0\u6642\u70b9\u306e price02 \u3092\u30b9\u30ca\u30c3\u30d7\u30b7\u30e7\u30c3\u30c8\u3057\u3066\u4fdd\u6301\u3059\u308b\uff08\u5f8c\u306e\u5024\u5f15\u304d\u3084\u30de\u30b9\u30bf\u6539\u5b9a\u306b\u5f71\u97ff\u3055\u308c\u306a\u3044\uff09\u3002BeMart \u5074\u3067\u306f `int` \u5186\u6574\u6570 Fake\u89b3\u5bdf\u6570\u5024 1200\u301c9800; \u89b3\u5bdf\u5024 '1200', '9800'\u3002","type":"integer","minimum":0,"maximum":999999999,"example":1200}},"additionalProperties":false,"$comment":"\u914d\u5217\u8981\u7d20\u306fFake/Resource\u3067\u89b3\u5bdf\u3055\u308c\u305f\u65e2\u77e5property\u306b\u56fa\u5b9a\u3059\u308b\u3002\u65b0\u3057\u3044\u5217\u304c\u5fc5\u8981\u306b\u306a\u3063\u305f\u5834\u5408\u306fSemantic-Ex\u89b3\u5bdf\u306b\u8ffd\u52a0\u3057\u3066schema\u3092\u66f4\u65b0\u3059\u308b\u3002"},"minItems":0} |  |
| paymentMethodId | int|null | 支払方法ID - 受注に紐づく支払方法マスタID。Fake/EC-CUBE境界ではDB採番値として扱う。 | Required | {"minimum":0,"maximum":2147483647} | 2 |
| customer | array|null|object | 会員詳細 - /shopping/confirm のレスポンスで扱う会員詳細。配列要素はALPS意味とFake観察に基づき、固定できない動的列は例外理由を台帳化する。 | Required | {"items":{"type":"string","title":"\u4f1a\u54e1","minLength":0,"maxLength":255,"description":"/shopping/confirm \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u4f1a\u54e1\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `customer` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002"},"minItems":0,"$comment":"\u5358\u4e00\u8a73\u7d30\u753b\u9762\u3067\u306f\u672a\u9078\u629e/\u521d\u671f\u8868\u793a\u306b\u7a7a\u914d\u5217\u3001\u53d6\u5f97\u6e08\u307f\u72b6\u614b\u306bobject\u304c\u73fe\u308c\u308b\u3002\u4e0d\u900f\u660e\u306a\u8a73\u7d30\u69cb\u9020\u306f\u65e2\u77e5property\u3092\u512a\u5148\u3057\u3001\u8ffd\u52a0\u30ad\u30fc\u306f\u4e92\u63db\u5883\u754c\u3068\u3057\u3066\u8a31\u5bb9\u3059\u308b\u3002"} |  |
| deliveryFeeTotal | int|null | 送料合計 - 全配送先の送料合計（スナップショット）。deliveryFeeAmount（地域別送料）+ deliveryFee（商品別送料）×数量 の合計。DeliveryFeePreprocessorで計算。カートと受注の両方で使用 Fake観察数値 0〜800; 観察値 '600', '0', '500', '800', '700'。 | Required | {"minimum":0,"maximum":999999999} | 600 |
| usePoint | int|null | 使用ポイント - 注文で使用するポイント数。実際の値引き額は usePoint x pointConversionRate（切り捨て）で計算され、不課税のポイント値引き明細として受注に追加 Fake観察数値 0〜0; 観察値 '0'。 | Required | {"minimum":0,"maximum":2147483647} | 0 |
| charge | int|null | 手数料 - 受注の決済手数料。paymentCharge（支払方法マスタの手数料）のスナップショット。PaymentChargePreprocessorにより受注作成時にコピーされる Fake観察数値 0〜300; 観察値 '0', '300', '200'。 | Required | {"minimum":0,"maximum":999999999} | 0 |
| paymentMethodName | string|null | 支払方法名 - 支払方法の表示名 Fake観察文字長 4〜8; 観察値 '代金引換', 'クレジットカード', '検証失敗'。 | Required | {"minLength":0,"maxLength":255} | 代金引換 |
| paymentTotal | int|null | 支払合計 - 実際の支払金額。初期値はtotalと同値で、PointProcessorがポイント値引きのOrderItem（type=POINT_DISCOUNT、不課税）を追加後にPurchaseFlow.calculateTotal()で再計算される。計算式: total - (利用ポイント x pointConversionRate) Fake観察数値 12700〜12700; 観察値 '12700'。 | Required | {"minimum":0,"maximum":999999999} | 12700 |
| preOrderId | string|null | 仮注文ID - 購入フローの一時セッショントークン（SHA1ハッシュ）。カートと受注を紐づける。予約注文（pre-order）IDではない。チェックアウト開始時に生成、注文確定またはカート破棄で消去 Fake観察文字長 40〜40; 観察値 'deadbeefcafe1234567890abcdef01234567890a', 'deadbeefcafe1234567890abcdef01234567890b', 'aaaa00000000000000000000000000000000aaaa', 'past00000000000000000000000000000000past', 'deadbeefcafe1234567890abcdef01234567890c', 'bbbb00000000000000000000000000000000bbbb', 'cccc00000000000000000000000000000000cccc', 'aceface0000000000000000000000000000a11ce'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} | deadbeefcafe1234567890abcdef01234567890a |
| tax | int|null | 税額 - 受注全体の税額合計（非推奨）。明細ごとの税額集計と差異が生じる場合があるため、正確な税額はOrderItem明細ごとのtaxを集計すべき Fake観察数値 1100〜1100; 観察値 '1100'。 | Required | {"minimum":0,"maximum":999999999} | 1100 |
| submitTo | object|null | フォーム送信先リンク - /shopping/confirm のフォーム送信に使う送信先リンク。HTTPメソッドと遷移先をまとめ、unsafe操作の入口を明示する。 | Optional | {"properties":{"href":{"title":"\u30ea\u30f3\u30afURI\u53c2\u7167\uff08URI\u53c2\u7167\uff09","description":"\u30da\u30fc\u30b8\u306eURL\u30d1\u30b9\uff08Symfony\u30eb\u30fc\u30c8\u540d\u3002\u4f8b: homepage, product_list\uff09","type":"string","format":"uri-reference","minLength":1,"maxLength":2048,"example":"/products"},"method":{"type":["string","null"],"enum":["get","post","put","patch","delete","GET","POST","PUT","PATCH","DELETE"],"title":"HTTP\u30e1\u30bd\u30c3\u30c9","description":"/shopping/confirm \u306e\u30ea\u30f3\u30af\u307e\u305f\u306f\u30d5\u30a9\u30fc\u30e0\u9001\u4fe1\u3067\u4f7f\u3046HTTP\u30e1\u30bd\u30c3\u30c9\u3002GET/POST\u7b49\u306e\u9077\u79fb\u65b9\u6cd5\u3092\u8868\u3059\u3002"}},"additionalProperties":false,"required":["href","method"]} |  |
| subtotal | int|null | 商品小計 - 商品合計金額（税込）。送料・手数料・値引き適用前の商品明細（orderItemType=1）のみの合計。PurchaseFlow.calculateSubTotal()で計算。送料無料条件の判定基準にも使用（お届け先ごとに判定） Fake観察数値 11000〜11000; 観察値 '11000'。 | Required | {"minimum":0,"maximum":999999999} | 11000 |
| transitionId | string | ALPS遷移ID - このレスポンス/操作が対応するALPS遷移ID。クライアントの状態遷移追跡に使う。 | Required | {"minLength":2,"maxLength":96,"pattern":"^(go|do)[A-Z][A-Za-z0-9]*$"} | doAddCartItem |
| discount | int|null | 値引き額 - 受注全体の値引き合計額。クーポン等による値引き Fake観察数値 0〜0; 観察値 '0'。 | Required | {"minimum":0,"maximum":999999999} | 0 |
| csrfToken | string|null | 処理識別子 - フォーム送信の偽造を防ぐために送信元画面で発行されるトークン。Fake環境では deterministic な値を使う。 | Optional | {"minLength":8,"maxLength":160,"pattern":"^[A-Za-z0-9_.:-]+$"} | fake-csrf-token-bemart-2026 |
| total | int|null | 受注合計 - 受注合計金額。計算式: subtotal(商品税込合計) + deliveryFeeTotal(送料) + charge(手数料) - discount(値引き)。カートのtotalPriceとは別プロパティ Fake観察数値 12700〜12700; 観察値 '12700'。 | Required | {"minimum":0,"maximum":999999999} | 12700 |

#### Links

| Relation | URL |
|----------|-----|
| doCheckout | [<code>page://self/shopping/checkout</code>](/shopping/checkout.md) |
| goShoppingError | [<code>page://self/shopping/error</code>](/shopping/error.md) |
## POST
HTML checkout form posts the selected payment field as `payment`.

Keep GET query compatibility while accepting the real browser form.

**ALPS**: `goShopping`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| preOrderId | string | 仮注文ID（入力） - 購入フローの一時セッショントークン（SHA1ハッシュ）。カートと受注を紐づける。予約注文（pre-order）IDではない。チェックアウト開始時に生成、注文確定またはカート破棄で消去 Fake観察文字長 40〜40; 観察値 'deadbeefcafe1234567890abcdef01234567890a', 'deadbeefcafe1234567890abcdef01234567890b', 'aaaa00000000000000000000000000000000aaaa', 'past00000000000000000000000000000000past', 'deadbeefcafe1234567890abcdef01234567890c', 'bbbb00000000000000000000000000000000bbbb', 'cccc00000000000000000000000000000000cccc', 'aceface0000000000000000000000000000a11ce'。 |  | Required | {"default":"aceface0000000000000000000000000000a11ce","minLength":0,"maxLength":128,"$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | deadbeefcafe1234567890abcdef01234567890a |
| payment | int | 支払方法ID（確認フォーム入力） - /shopping の確認フォームで選択された支払方法ID。HTML formでは `payment` 名で送信され、Resourceで ConfirmOrderInput.paymentMethodId に渡す。 | 2 | Optional | {"default":2,"minLength":0,"maxLength":128,"$comment":"\u652f\u6255\u65b9\u6cd5ID\u306f\u696d\u52d9\u4e0aID\u3060\u304c\u3001HTTP\u30d5\u30a9\u30fc\u30e0\u3067\u306f\u6587\u5b57\u5217\u3068\u3057\u3066\u5c4a\u304f\u3002Resource/Semantic\u5c64\u306e\u691c\u8a3c\u3092\u901a\u3059\u305f\u3081transport schema\u3067\u306fstring|integer\u3092\u8a31\u5bb9\u3059\u308b\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 2 |


### Response

[Object: GET /shopping/confirm response](../schemas/get-shopping-confirm.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| addPoint | int|null | 付与ポイント - 注文により付与されるポイント数。商品単価(税抜) x pointRate x 数量で明細ごとに計算し合算。利用ポイント分を控除。発送済み(DELIVERED)遷移時に会員のpointに加算 Fake観察数値 0〜127; 観察値 '127', '0'。 | Required | {"minimum":0,"maximum":2147483647} | 127 |
| items | array|null | 明細一覧 - /shopping/confirm の親オブジェクト `` に含まれる明細配列。商品・カート・受注明細の文脈で解釈する。 | Required | {"items":{"type":["object","null"],"title":"\u660e\u7d30","description":"/shopping/confirm \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u660e\u7d30\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `items` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002","properties":{"productName":{"type":["string","null"],"minLength":0,"maxLength":128,"title":"\u5546\u54c1\u540d","description":"\u5546\u54c1\u306e\u8868\u793a\u540d Fake\u89b3\u5bdf\u6587\u5b57\u9577 6\u301c17\u3002","example":"\u30b5\u30f3\u30d7\u30eb\u5546\u54c1 A"},"productCode":{"title":"\u5546\u54c1\u30b3\u30fc\u30c9","description":"SKU/\u54c1\u756a\u3002\u5728\u5eab\u7ba1\u7406\u3084\u53d7\u6ce8\u660e\u7d30\u3067\u306e\u8b58\u5225\u306b\u4f7f\u7528 \u5546\u54c1\u3092\u8b58\u5225\u3059\u308bSKU\u3002Fake corpus\u3067\u306fASCII\u82f1\u6570\u30fb\u30cf\u30a4\u30d5\u30f3\u4e2d\u5fc3\u3067\u3001\u53d7\u6ce8\u660e\u7d30/\u30ab\u30fc\u30c8\u660e\u7d30\u306e\u7d50\u5408\u30ad\u30fc\u306b\u306a\u308b\u3002 Fake\u89b3\u5bdf\u6587\u5b57\u9577 10\u301c26\u3002","type":"string","minLength":0,"maxLength":64,"example":"sample-001"},"quantity":{"title":"\u6570\u91cf","description":"\u8cfc\u5165\u6570\u91cf\u3002\u30ab\u30fc\u30c8\u660e\u7d30\u3068\u53d7\u6ce8\u660e\u7d30\u3067\u5171\u901a\u4f7f\u7528 Fake\u89b3\u5bdf\u6570\u5024 1\u301c3; \u89b3\u5bdf\u5024 '1', '2', '3'\u3002","type":"integer","minimum":1,"maximum":999,"example":1},"totalPrice":{"title":"\u30ab\u30fc\u30c8\u5408\u8a08\u91d1\u984d","description":"\u30ab\u30fc\u30c8\u5185\u306e\u7a0e\u8fbc\u5408\u8a08\u91d1\u984d\u3002PurchaseFlow.calculateTotal()\u3067\u6bce\u56de\u518d\u8a08\u7b97\u3055\u308c\u308b\u30ad\u30e3\u30c3\u30b7\u30e5\u5024\u3002\u53d7\u6ce8\u306etotal\u3068\u306f\u5225\u30d7\u30ed\u30d1\u30c6\u30a3 Fake\u89b3\u5bdf\u6570\u5024 0\u301c3600; \u89b3\u5bdf\u5024 '0', '1500', '3600'\u3002","type":"integer","minimum":0,"maximum":999999999,"example":0},"unitPrice":{"title":"\u5358\u4fa1\uff08\u8868\u793a/\u8a08\u7b97\u7528\uff09","description":"\u660e\u7d301\u4ef6\u3042\u305f\u308a\u306e\u5358\u4fa1\u3002\u53d7\u6ce8/\u30ab\u30fc\u30c8\u660e\u7d30\u30fb\u304a\u6c17\u306b\u5165\u308a\u30b9\u30ca\u30c3\u30d7\u30b7\u30e7\u30c3\u30c8\u3067\u306f\u8ffd\u52a0\u6642\u70b9\u306e price02 \u3092\u30b9\u30ca\u30c3\u30d7\u30b7\u30e7\u30c3\u30c8\u3057\u3066\u4fdd\u6301\u3059\u308b\uff08\u5f8c\u306e\u5024\u5f15\u304d\u3084\u30de\u30b9\u30bf\u6539\u5b9a\u306b\u5f71\u97ff\u3055\u308c\u306a\u3044\uff09\u3002BeMart \u5074\u3067\u306f `int` \u5186\u6574\u6570 Fake\u89b3\u5bdf\u6570\u5024 1200\u301c9800; \u89b3\u5bdf\u5024 '1200', '9800'\u3002","type":"integer","minimum":0,"maximum":999999999,"example":1200}},"additionalProperties":false,"$comment":"\u914d\u5217\u8981\u7d20\u306fFake/Resource\u3067\u89b3\u5bdf\u3055\u308c\u305f\u65e2\u77e5property\u306b\u56fa\u5b9a\u3059\u308b\u3002\u65b0\u3057\u3044\u5217\u304c\u5fc5\u8981\u306b\u306a\u3063\u305f\u5834\u5408\u306fSemantic-Ex\u89b3\u5bdf\u306b\u8ffd\u52a0\u3057\u3066schema\u3092\u66f4\u65b0\u3059\u308b\u3002"},"minItems":0} |  |
| paymentMethodId | int|null | 支払方法ID - 受注に紐づく支払方法マスタID。Fake/EC-CUBE境界ではDB採番値として扱う。 | Required | {"minimum":0,"maximum":2147483647} | 2 |
| customer | array|null|object | 会員詳細 - /shopping/confirm のレスポンスで扱う会員詳細。配列要素はALPS意味とFake観察に基づき、固定できない動的列は例外理由を台帳化する。 | Required | {"items":{"type":"string","title":"\u4f1a\u54e1","minLength":0,"maxLength":255,"description":"/shopping/confirm \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u4f1a\u54e1\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `customer` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002"},"minItems":0,"$comment":"\u5358\u4e00\u8a73\u7d30\u753b\u9762\u3067\u306f\u672a\u9078\u629e/\u521d\u671f\u8868\u793a\u306b\u7a7a\u914d\u5217\u3001\u53d6\u5f97\u6e08\u307f\u72b6\u614b\u306bobject\u304c\u73fe\u308c\u308b\u3002\u4e0d\u900f\u660e\u306a\u8a73\u7d30\u69cb\u9020\u306f\u65e2\u77e5property\u3092\u512a\u5148\u3057\u3001\u8ffd\u52a0\u30ad\u30fc\u306f\u4e92\u63db\u5883\u754c\u3068\u3057\u3066\u8a31\u5bb9\u3059\u308b\u3002"} |  |
| deliveryFeeTotal | int|null | 送料合計 - 全配送先の送料合計（スナップショット）。deliveryFeeAmount（地域別送料）+ deliveryFee（商品別送料）×数量 の合計。DeliveryFeePreprocessorで計算。カートと受注の両方で使用 Fake観察数値 0〜800; 観察値 '600', '0', '500', '800', '700'。 | Required | {"minimum":0,"maximum":999999999} | 600 |
| usePoint | int|null | 使用ポイント - 注文で使用するポイント数。実際の値引き額は usePoint x pointConversionRate（切り捨て）で計算され、不課税のポイント値引き明細として受注に追加 Fake観察数値 0〜0; 観察値 '0'。 | Required | {"minimum":0,"maximum":2147483647} | 0 |
| charge | int|null | 手数料 - 受注の決済手数料。paymentCharge（支払方法マスタの手数料）のスナップショット。PaymentChargePreprocessorにより受注作成時にコピーされる Fake観察数値 0〜300; 観察値 '0', '300', '200'。 | Required | {"minimum":0,"maximum":999999999} | 0 |
| paymentMethodName | string|null | 支払方法名 - 支払方法の表示名 Fake観察文字長 4〜8; 観察値 '代金引換', 'クレジットカード', '検証失敗'。 | Required | {"minLength":0,"maxLength":255} | 代金引換 |
| paymentTotal | int|null | 支払合計 - 実際の支払金額。初期値はtotalと同値で、PointProcessorがポイント値引きのOrderItem（type=POINT_DISCOUNT、不課税）を追加後にPurchaseFlow.calculateTotal()で再計算される。計算式: total - (利用ポイント x pointConversionRate) Fake観察数値 12700〜12700; 観察値 '12700'。 | Required | {"minimum":0,"maximum":999999999} | 12700 |
| preOrderId | string|null | 仮注文ID - 購入フローの一時セッショントークン（SHA1ハッシュ）。カートと受注を紐づける。予約注文（pre-order）IDではない。チェックアウト開始時に生成、注文確定またはカート破棄で消去 Fake観察文字長 40〜40; 観察値 'deadbeefcafe1234567890abcdef01234567890a', 'deadbeefcafe1234567890abcdef01234567890b', 'aaaa00000000000000000000000000000000aaaa', 'past00000000000000000000000000000000past', 'deadbeefcafe1234567890abcdef01234567890c', 'bbbb00000000000000000000000000000000bbbb', 'cccc00000000000000000000000000000000cccc', 'aceface0000000000000000000000000000a11ce'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} | deadbeefcafe1234567890abcdef01234567890a |
| tax | int|null | 税額 - 受注全体の税額合計（非推奨）。明細ごとの税額集計と差異が生じる場合があるため、正確な税額はOrderItem明細ごとのtaxを集計すべき Fake観察数値 1100〜1100; 観察値 '1100'。 | Required | {"minimum":0,"maximum":999999999} | 1100 |
| submitTo | object|null | フォーム送信先リンク - /shopping/confirm のフォーム送信に使う送信先リンク。HTTPメソッドと遷移先をまとめ、unsafe操作の入口を明示する。 | Optional | {"properties":{"href":{"title":"\u30ea\u30f3\u30afURI\u53c2\u7167\uff08URI\u53c2\u7167\uff09","description":"\u30da\u30fc\u30b8\u306eURL\u30d1\u30b9\uff08Symfony\u30eb\u30fc\u30c8\u540d\u3002\u4f8b: homepage, product_list\uff09","type":"string","format":"uri-reference","minLength":1,"maxLength":2048,"example":"/products"},"method":{"type":["string","null"],"enum":["get","post","put","patch","delete","GET","POST","PUT","PATCH","DELETE"],"title":"HTTP\u30e1\u30bd\u30c3\u30c9","description":"/shopping/confirm \u306e\u30ea\u30f3\u30af\u307e\u305f\u306f\u30d5\u30a9\u30fc\u30e0\u9001\u4fe1\u3067\u4f7f\u3046HTTP\u30e1\u30bd\u30c3\u30c9\u3002GET/POST\u7b49\u306e\u9077\u79fb\u65b9\u6cd5\u3092\u8868\u3059\u3002"}},"additionalProperties":false,"required":["href","method"]} |  |
| subtotal | int|null | 商品小計 - 商品合計金額（税込）。送料・手数料・値引き適用前の商品明細（orderItemType=1）のみの合計。PurchaseFlow.calculateSubTotal()で計算。送料無料条件の判定基準にも使用（お届け先ごとに判定） Fake観察数値 11000〜11000; 観察値 '11000'。 | Required | {"minimum":0,"maximum":999999999} | 11000 |
| transitionId | string | ALPS遷移ID - このレスポンス/操作が対応するALPS遷移ID。クライアントの状態遷移追跡に使う。 | Required | {"minLength":2,"maxLength":96,"pattern":"^(go|do)[A-Z][A-Za-z0-9]*$"} | doAddCartItem |
| discount | int|null | 値引き額 - 受注全体の値引き合計額。クーポン等による値引き Fake観察数値 0〜0; 観察値 '0'。 | Required | {"minimum":0,"maximum":999999999} | 0 |
| csrfToken | string|null | 処理識別子 - フォーム送信の偽造を防ぐために送信元画面で発行されるトークン。Fake環境では deterministic な値を使う。 | Optional | {"minLength":8,"maxLength":160,"pattern":"^[A-Za-z0-9_.:-]+$"} | fake-csrf-token-bemart-2026 |
| total | int|null | 受注合計 - 受注合計金額。計算式: subtotal(商品税込合計) + deliveryFeeTotal(送料) + charge(手数料) - discount(値引き)。カートのtotalPriceとは別プロパティ Fake観察数値 12700〜12700; 観察値 '12700'。 | Required | {"minimum":0,"maximum":999999999} | 12700 |

#### Links

| Relation | URL |
|----------|-----|
| doCheckout | [<code>page://self/shopping/checkout</code>](/shopping/checkout.md) |
| goShoppingError | [<code>page://self/shopping/error</code>](/shopping/error.md) |