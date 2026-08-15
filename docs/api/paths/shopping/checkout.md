<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /shopping/checkout
EC-CUBE doCheckout —注文確定 (Shopping/Checkout).

Resource is the HTTP entry point: builds CheckoutInput, hands it to
Becoming, and projects the resulting CheckoutCompleted into the
ShoppingComplete response body. Pilot 5 deliberately maps Reason-thrown
DomainExceptions to HTTP codes (ShoppingError 422 / 404) rather than
routing through a Branching Final — Branching itself was already covered
by Pilot 3, so we keep the failure path simple.

Failure mapping (per `be/docs/pilot5/alps-analyze.md` §例外フロー):
  - PreOrderNotFoundException           → 404 (the pre-order never existed)
  - UnauthorizedPreOrderAccessException → 403 (not the owner; Pilot 5 F-1)
  - PreOrderAlreadyClaimedException     → 409 (another request is already
                                           completing this pre-order)
  - InsufficientStockException          → 422 (stock cannot fulfill the order)
  - PaymentDeclinedException            → 422 (gateway refused the charge)
  - SemanticVariableException           → 400 (preOrderId malformed)

Note: paymentMethodId is intentionally NOT accepted here. It is sourced
from the persisted OrderEntity inside CheckoutSettled to prevent
mass-assignment tampering (Pilot 5 F-2).




## POST
Phase B Slice 9: the domain parameter arrives from the HTTP request body.

`$preOrderId` is a 40-hex-char id that PreOrderId Semantic
format-validates. The CSRF boundary token is enforced declaratively by
the CsrfProtected attribute.

**ALPS**: `doCheckout` - 注文を確定する



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| preOrderId | string | 仮注文ID（入力） - 購入フローの一時セッショントークン（SHA1ハッシュ）。カートと受注を紐づける。予約注文（pre-order）IDではない。チェックアウト開始時に生成、注文確定またはカート破棄で消去 Fake観察文字長 40〜40; 観察値 'deadbeefcafe1234567890abcdef01234567890a', 'deadbeefcafe1234567890abcdef01234567890b', 'aaaa00000000000000000000000000000000aaaa', 'past00000000000000000000000000000000past', 'deadbeefcafe1234567890abcdef01234567890c', 'bbbb00000000000000000000000000000000bbbb', 'cccc00000000000000000000000000000000cccc', 'aceface0000000000000000000000000000a11ce'。 |  | Required | {"minLength":0,"maxLength":128,"$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | deadbeefcafe1234567890abcdef01234567890a |


### Response

[Object: POST /shopping/checkout response](../schemas/post-shopping-checkout.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| addPoint | int|null | 付与ポイント - 注文により付与されるポイント数。商品単価(税抜) x pointRate x 数量で明細ごとに計算し合算。利用ポイント分を控除。発送済み(DELIVERED)遷移時に会員のpointに加算 Fake観察数値 0〜127; 観察値 '127', '0'。 | Required | {"minimum":0,"maximum":2147483647} | 127 |
| orderDate | string | 注文日 - 注文確定日時 Fake観察文字長 19〜19; 観察値 '2026-04-01 10:00:00'。 | Required | {"pattern":"^\\d{4}-\\d{2}-\\d{2}([ T]\\d{2}:\\d{2}:\\d{2}([+-]\\d{2}:?\\d{2}|Z)?)?$"} | 2026-04-01 10:00:00 |
| paymentTotal | int|null | 支払合計 - 実際の支払金額。初期値はtotalと同値で、PointProcessorがポイント値引きのOrderItem（type=POINT_DISCOUNT、不課税）を追加後にPurchaseFlow.calculateTotal()で再計算される。計算式: total - (利用ポイント x pointConversionRate) Fake観察数値 12700〜12700; 観察値 '12700'。 | Required | {"minimum":0,"maximum":999999999} | 12700 |
| completeMessage | string|null | 注文完了メッセージ - 注文完了画面に表示するメッセージ。主に決済プラグインが設定するカスタムメッセージ。複数プラグインからの利用を想定しappendCompleteMesssage()で追記する。HTML使用可 | Required | {"minLength":0,"maxLength":255} |  |
| paymentDate | string | 入金日 - 入金確認日時。入金済みステータスへの変更時に記録 Fake観察文字長 19〜19; 観察値 '2026-04-01 10:00:00'。 | Required | {"$comment":"\u672a\u5165\u91d1\u30fb\u672a\u767a\u9001\u30fb\u672a\u516c\u958b\u306a\u3069\u672a\u78ba\u5b9a\u65e5\u6642\u306fEC-CUBE\u5883\u754c\u3067\u7a7a\u6587\u5b57\u3068\u3057\u3066\u73fe\u308c\u308b\u305f\u3081\u3001\u65e5\u4ed8/\u65e5\u6642\u6587\u5b57\u5217\u306b\u52a0\u3048\u3066\u7a7a\u6587\u5b57\u3092\u8a31\u5bb9\u3059\u308b\u3002","pattern":"^$|\\d{4}-\\d{2}-\\d{2}([ T]\\d{2}:\\d{2}:\\d{2}([+-]\\d{2}:?\\d{2}|Z)?)?$"} | 2026-04-01 10:00:00 |
| orderNo | string|null | 注文番号 - 顧客向けの注文番号。フォーマットはカスタマイズ可能 Fake観察文字長 32〜32; 観察値 'past0000000000000000000000000001'。 | Required | {"minLength":0,"maxLength":64} | past0000000000000000000000000001 |
| customerId | string|null | 会員ID - dtb_customer.id の不透明な文字列ハンドル。BeMart の Entity 層は数値ではなく文字列として保持する（マスアサインメント防止のため、Session/AuthZ 経由で読み出し、リクエスト本文からは受け取らない）。Favorite / Cart / Order の所有者キーとして横断使用 Fake観察文字長 12〜32; 観察値 'customer-001', '0123456789abcdef0123456789abcdef', 'customer-002', 'favorite-list-customer', 'favorite-html-customer', 'fedcba9876543210fedcba9876543210', 'aaaaaaaa00000000bbbbbbbb11111111', '10000000aaaa1111bbbb2222cccc3333'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} | customer-001 |
| total | int|null | 受注合計 - 受注合計金額。計算式: subtotal(商品税込合計) + deliveryFeeTotal(送料) + charge(手数料) - discount(値引き)。カートのtotalPriceとは別プロパティ Fake観察数値 12700〜12700; 観察値 '12700'。 | Required | {"minimum":0,"maximum":999999999} | 12700 |
| orderStatus | int | 受注ステータス - 1=新規受付, 3=注文取消, 4=対応中, 5=発送済み, 6=入金済み, 7=決済処理中, 8=購入処理中, 9=返品。Symfony Workflowステートマシンで遷移を制御。許可される遷移: pay(1->6), packing(1,6->4), cancel(1,4,6->3), back_to_in_progress(3->4), ship(1,6,4->5), return(5->9), cancel_return(9->5)。7と8はPurchaseFlow内で直接セットされステートマシン遷移の対象外 Fake観察数値 1〜1; 観察値 '1'。 | Required | {"minimum":1,"maximum":9} | 1 |

#### Links

| Relation | URL |
|----------|-----|
| goTop | [<code>page://self/</code>](/.md) |
| goCart | [<code>page://self/cart</code>](/cart.md) |