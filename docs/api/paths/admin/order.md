<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/order
EC-CUBE goOrder / doUpdateOrder — 受注詳細 (Wave 7).

- GET → goOrder        (read header + items + customer summary)
  - PUT → doUpdateOrder  (partial-update: discount / charge / usePoint)

The status-flip flow (doUpdateOrderStatus) lives at a sibling resource
`/admin/order-status` ({@see \OrderStatus}) — it is a sub-resource of
the order with workflow-significant semantics, so we keep its URL
distinct rather than overloading PUT here. Choice (B) from the Wave 7
design note.

Admin-only — both methods raise {@see \UnauthorizedAdminAccessException}
via the Be Final when the admin firewall is unset. CSRF is enforced
on PUT only (read-only GET does not need a token).

Failure mapping (cross-firewall AUTHZ → existence ladder):
  - Invalid CSRF (PUT)                    → 403
  - SemanticVariableException             → 400 (input format)
  - UnauthorizedAdminAccessException      → 403 (no admin session)
  - OrderNotFoundException                → 404 (unknown orderNo)

The 403-before-404 ordering matches the Be Final's check sequence —
an admin-anonymous client learns NOTHING about which orderNos resolve.

Mass-assignment safety (PUT): see {@see \AdminUpdateOrderInput} — only
discount / charge / usePoint are editable. `orderNo` IS in the body
because it is the target selector (admin needs to pick which order),
but `customerId` / `total` / `orderStatus` are NOT writable from
here.




## GET
Wave 7: orderNo comes from the admin UI (click on an order-list
row, or pasted into the URL).

**ALPS**: `goOrder`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| orderNo | string | 注文番号（入力） - 顧客向けの注文番号。フォーマットはカスタマイズ可能 Fake観察文字長 32〜32; 観察値 'past0000000000000000000000000001'。 |  | Required | {"minLength":0,"maxLength":64,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | past0000000000000000000000000001 |


### Response

[Object: GET /admin/order response](../schemas/get-admin-order.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| paymentDate | string | 入金日 - 入金確認日時。入金済みステータスへの変更時に記録 Fake観察文字長 19〜19; 観察値 '2026-04-01 10:00:00'。 | Required | {"$comment":"\u672a\u5165\u91d1\u30fb\u672a\u767a\u9001\u30fb\u672a\u516c\u958b\u306a\u3069\u672a\u78ba\u5b9a\u65e5\u6642\u306fEC-CUBE\u5883\u754c\u3067\u7a7a\u6587\u5b57\u3068\u3057\u3066\u73fe\u308c\u308b\u305f\u3081\u3001\u65e5\u4ed8/\u65e5\u6642\u6587\u5b57\u5217\u306b\u52a0\u3048\u3066\u7a7a\u6587\u5b57\u3092\u8a31\u5bb9\u3059\u308b\u3002","pattern":"^$|\\d{4}-\\d{2}-\\d{2}([ T]\\d{2}:\\d{2}:\\d{2}([+-]\\d{2}:?\\d{2}|Z)?)?$"} | 2026-04-01 10:00:00 |
| paymentMethodId | int|null | 支払方法ID - 受注に紐づく支払方法マスタID。Fake/EC-CUBE境界ではDB採番値として扱う。 | Required | {"minimum":0,"maximum":2147483647} | 2 |
| orderNo | string|null | 注文番号 - 顧客向けの注文番号。フォーマットはカスタマイズ可能 Fake観察文字長 32〜32; 観察値 'past0000000000000000000000000001'。 | Required | {"minLength":0,"maxLength":64} | past0000000000000000000000000001 |
| orderStatus | int | 受注ステータス - 1=新規受付, 3=注文取消, 4=対応中, 5=発送済み, 6=入金済み, 7=決済処理中, 8=購入処理中, 9=返品。Symfony Workflowステートマシンで遷移を制御。許可される遷移: pay(1->6), packing(1,6->4), cancel(1,4,6->3), back_to_in_progress(3->4), ship(1,6,4->5), return(5->9), cancel_return(9->5)。7と8はPurchaseFlow内で直接セットされステートマシン遷移の対象外 Fake観察数値 1〜1; 観察値 '1'。 | Required | {"minimum":1,"maximum":9} | 1 |
| orderDate | string | 注文日 - 注文確定日時 Fake観察文字長 19〜19; 観察値 '2026-04-01 10:00:00'。 | Required | {"pattern":"^\\d{4}-\\d{2}-\\d{2}([ T]\\d{2}:\\d{2}:\\d{2}([+-]\\d{2}:?\\d{2}|Z)?)?$"} | 2026-04-01 10:00:00 |
| preOrderId | string|null | 仮注文ID - 購入フローの一時セッショントークン（SHA1ハッシュ）。カートと受注を紐づける。予約注文（pre-order）IDではない。チェックアウト開始時に生成、注文確定またはカート破棄で消去 Fake観察文字長 40〜40; 観察値 'deadbeefcafe1234567890abcdef01234567890a', 'deadbeefcafe1234567890abcdef01234567890b', 'aaaa00000000000000000000000000000000aaaa', 'past00000000000000000000000000000000past', 'deadbeefcafe1234567890abcdef01234567890c', 'bbbb00000000000000000000000000000000bbbb', 'cccc00000000000000000000000000000000cccc', 'aceface0000000000000000000000000000a11ce'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} | deadbeefcafe1234567890abcdef01234567890a |
| itemCount | int|null | 明細件数 - /admin/order のレスポンスで返す明細件数。一覧・集計・処理結果の規模を表す非負整数。 | Required | {"minimum":0,"maximum":10000} | 1 |
| discount | int|null | 値引き額 - 受注全体の値引き合計額。クーポン等による値引き Fake観察数値 0〜0; 観察値 '0'。 | Required | {"minimum":0,"maximum":999999999} | 0 |
| addPoint | int|null | 付与ポイント - 注文により付与されるポイント数。商品単価(税抜) x pointRate x 数量で明細ごとに計算し合算。利用ポイント分を控除。発送済み(DELIVERED)遷移時に会員のpointに加算 Fake観察数値 0〜127; 観察値 '127', '0'。 | Required | {"minimum":0,"maximum":2147483647} | 127 |
| items | array|null | 明細一覧 - /admin/order の親オブジェクト `` に含まれる明細配列。商品・カート・受注明細の文脈で解釈する。 | Required | {"items":{"type":["object","null"],"title":"\u660e\u7d30","description":"/admin/order \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u660e\u7d30\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `items` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002","properties":{"productName":{"type":["string","null"],"minLength":0,"maxLength":128,"title":"\u5546\u54c1\u540d","description":"\u5546\u54c1\u306e\u8868\u793a\u540d Fake\u89b3\u5bdf\u6587\u5b57\u9577 6\u301c17\u3002","example":"\u30b5\u30f3\u30d7\u30eb\u5546\u54c1 A"},"productCode":{"title":"\u5546\u54c1\u30b3\u30fc\u30c9","description":"SKU/\u54c1\u756a\u3002\u5728\u5eab\u7ba1\u7406\u3084\u53d7\u6ce8\u660e\u7d30\u3067\u306e\u8b58\u5225\u306b\u4f7f\u7528 \u5546\u54c1\u3092\u8b58\u5225\u3059\u308bSKU\u3002Fake corpus\u3067\u306fASCII\u82f1\u6570\u30fb\u30cf\u30a4\u30d5\u30f3\u4e2d\u5fc3\u3067\u3001\u53d7\u6ce8\u660e\u7d30/\u30ab\u30fc\u30c8\u660e\u7d30\u306e\u7d50\u5408\u30ad\u30fc\u306b\u306a\u308b\u3002 Fake\u89b3\u5bdf\u6587\u5b57\u9577 10\u301c26\u3002","type":"string","minLength":0,"maxLength":64,"example":"sample-001"},"quantity":{"title":"\u6570\u91cf","description":"\u8cfc\u5165\u6570\u91cf\u3002\u30ab\u30fc\u30c8\u660e\u7d30\u3068\u53d7\u6ce8\u660e\u7d30\u3067\u5171\u901a\u4f7f\u7528 Fake\u89b3\u5bdf\u6570\u5024 1\u301c3; \u89b3\u5bdf\u5024 '1', '2', '3'\u3002","type":"integer","minimum":1,"maximum":999,"example":1},"unitPrice":{"title":"\u5358\u4fa1\uff08\u8868\u793a/\u8a08\u7b97\u7528\uff09","description":"\u660e\u7d301\u4ef6\u3042\u305f\u308a\u306e\u5358\u4fa1\u3002\u53d7\u6ce8/\u30ab\u30fc\u30c8\u660e\u7d30\u30fb\u304a\u6c17\u306b\u5165\u308a\u30b9\u30ca\u30c3\u30d7\u30b7\u30e7\u30c3\u30c8\u3067\u306f\u8ffd\u52a0\u6642\u70b9\u306e price02 \u3092\u30b9\u30ca\u30c3\u30d7\u30b7\u30e7\u30c3\u30c8\u3057\u3066\u4fdd\u6301\u3059\u308b\uff08\u5f8c\u306e\u5024\u5f15\u304d\u3084\u30de\u30b9\u30bf\u6539\u5b9a\u306b\u5f71\u97ff\u3055\u308c\u306a\u3044\uff09\u3002BeMart \u5074\u3067\u306f `int` \u5186\u6574\u6570 Fake\u89b3\u5bdf\u6570\u5024 1200\u301c9800; \u89b3\u5bdf\u5024 '1200', '9800'\u3002","type":"integer","minimum":0,"maximum":999999999,"example":1200}},"additionalProperties":false,"$comment":"\u914d\u5217\u8981\u7d20\u306fFake/Resource\u3067\u89b3\u5bdf\u3055\u308c\u305f\u65e2\u77e5property\u306b\u56fa\u5b9a\u3059\u308b\u3002\u65b0\u3057\u3044\u5217\u304c\u5fc5\u8981\u306b\u306a\u3063\u305f\u5834\u5408\u306fSemantic-Ex\u89b3\u5bdf\u306b\u8ffd\u52a0\u3057\u3066schema\u3092\u66f4\u65b0\u3059\u308b\u3002"},"minItems":0} |  |
| customer | array|null|object | 会員詳細 - /admin/order のレスポンスで扱う会員詳細。配列要素はALPS意味とFake観察に基づき、固定できない動的列は例外理由を台帳化する。 | Required | {"items":{"type":"string","title":"\u4f1a\u54e1","minLength":0,"maxLength":255,"description":"/admin/order \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u4f1a\u54e1\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `customer` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002"},"minItems":0,"$comment":"\u5358\u4e00\u8a73\u7d30\u753b\u9762\u3067\u306f\u672a\u9078\u629e/\u521d\u671f\u8868\u793a\u306b\u7a7a\u914d\u5217\u3001\u53d6\u5f97\u6e08\u307f\u72b6\u614b\u306bobject\u304c\u73fe\u308c\u308b\u3002\u4e0d\u900f\u660e\u306a\u8a73\u7d30\u69cb\u9020\u306f\u65e2\u77e5property\u3092\u512a\u5148\u3057\u3001\u8ffd\u52a0\u30ad\u30fc\u306f\u4e92\u63db\u5883\u754c\u3068\u3057\u3066\u8a31\u5bb9\u3059\u308b\u3002"} |  |
| customerId | string|null | 会員ID - dtb_customer.id の不透明な文字列ハンドル。BeMart の Entity 層は数値ではなく文字列として保持する（マスアサインメント防止のため、Session/AuthZ 経由で読み出し、リクエスト本文からは受け取らない）。Favorite / Cart / Order の所有者キーとして横断使用 Fake観察文字長 12〜32; 観察値 'customer-001', '0123456789abcdef0123456789abcdef', 'customer-002', 'favorite-list-customer', 'favorite-html-customer', 'fedcba9876543210fedcba9876543210', 'aaaaaaaa00000000bbbbbbbb11111111', '10000000aaaa1111bbbb2222cccc3333'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} | customer-001 |
| deliveryFeeTotal | int|null | 送料合計 - 全配送先の送料合計（スナップショット）。deliveryFeeAmount（地域別送料）+ deliveryFee（商品別送料）×数量 の合計。DeliveryFeePreprocessorで計算。カートと受注の両方で使用 Fake観察数値 0〜800; 観察値 '600', '0', '500', '800', '700'。 | Required | {"minimum":0,"maximum":999999999} | 600 |
| usePoint | int|null | 使用ポイント - 注文で使用するポイント数。実際の値引き額は usePoint x pointConversionRate（切り捨て）で計算され、不課税のポイント値引き明細として受注に追加 Fake観察数値 0〜0; 観察値 '0'。 | Required | {"minimum":0,"maximum":2147483647} | 0 |
| charge | int|null | 手数料 - 受注の決済手数料。paymentCharge（支払方法マスタの手数料）のスナップショット。PaymentChargePreprocessorにより受注作成時にコピーされる Fake観察数値 0〜300; 観察値 '0', '300', '200'。 | Required | {"minimum":0,"maximum":999999999} | 0 |
| paymentTotal | int|null | 支払合計 - 実際の支払金額。初期値はtotalと同値で、PointProcessorがポイント値引きのOrderItem（type=POINT_DISCOUNT、不課税）を追加後にPurchaseFlow.calculateTotal()で再計算される。計算式: total - (利用ポイント x pointConversionRate) Fake観察数値 12700〜12700; 観察値 '12700'。 | Required | {"minimum":0,"maximum":999999999} | 12700 |
| tax | int|null | 税額 - 受注全体の税額合計（非推奨）。明細ごとの税額集計と差異が生じる場合があるため、正確な税額はOrderItem明細ごとのtaxを集計すべき Fake観察数値 1100〜1100; 観察値 '1100'。 | Required | {"minimum":0,"maximum":999999999} | 1100 |
| orderStatusOptions | object | 受注ステータス - 1=新規受付, 3=注文取消, 4=対応中, 5=発送済み, 6=入金済み, 7=決済処理中, 8=購入処理中, 9=返品。Symfony Workflowステートマシンで遷移を制御。許可される遷移: pay(1->6), packing(1,6->4), cancel(1,4,6->3), back_to_in_progress(3->4), ship(1,6,4->5), return(5->9), cancel_return(9->5)。7と8はPurchaseFlow内で直接セットされステートマシン遷移の対象外 | Required | {"$comment":"\u30d5\u30ec\u30fc\u30e0\u30ef\u30fc\u30af\u7531\u6765\u307e\u305f\u306f\u52d5\u7684map\u306e\u305f\u3081\u3001JSON\u5883\u754c\u3067\u306fobject\u3067\u3042\u308b\u3053\u3068\u3068\u610f\u5473\u3092\u5951\u7d04\u3057\u3001\u5185\u90e8\u30ad\u30fc\u306f\u5225\u5883\u754c\u3067\u6271\u3046\u3002\u8ffd\u52a0\u30ad\u30fc\u306f\u4e0d\u900f\u660e\u69cb\u9020\u3068\u3057\u3066\u8a31\u5bb9\u3059\u308b\u3002"} |  |
| subtotal | int|null | 商品小計 - 商品合計金額（税込）。送料・手数料・値引き適用前の商品明細（orderItemType=1）のみの合計。PurchaseFlow.calculateSubTotal()で計算。送料無料条件の判定基準にも使用（お届け先ごとに判定） Fake観察数値 11000〜11000; 観察値 '11000'。 | Required | {"minimum":0,"maximum":999999999} | 11000 |
| csrfToken | string|null | 処理識別子 - フォーム送信の偽造を防ぐために送信元画面で発行されるトークン。Fake環境では deterministic な値を使う。 | Optional | {"minLength":8,"maxLength":160,"pattern":"^[A-Za-z0-9_.:-]+$"} | fake-csrf-token-bemart-2026 |
| total | int|null | 受注合計 - 受注合計金額。計算式: subtotal(商品税込合計) + deliveryFeeTotal(送料) + charge(手数料) - discount(値引き)。カートのtotalPriceとは別プロパティ Fake観察数値 12700〜12700; 観察値 '12700'。 | Required | {"minimum":0,"maximum":999999999} | 12700 |

#### Links

| Relation | URL |
|----------|-----|
| goOrderList | [<code>page://self/admin/order-list</code>](/admin/order-list.md) |
| doUpdateOrder | [<code>page://self/admin/order</code>](/admin/order.md) |
| doUpdateOrderStatus | [<code>page://self/admin/order-status</code>](/admin/order-status.md) |
## PUT
Wave 7: every editable field is admin-form input. The orderNo
selector is also admin-controlled. Same taint discipline as the
Wave 5 / Wave 6 admin resources.

**ALPS**: `doUpdateOrder`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| orderNo | string | 注文番号（入力） - 顧客向けの注文番号。フォーマットはカスタマイズ可能 Fake観察文字長 32〜32; 観察値 'past0000000000000000000000000001'。 |  | Required | {"minLength":0,"maxLength":64,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | past0000000000000000000000000001 |
| discount | int | 値引き額（入力） - 受注全体の値引き合計額。クーポン等による値引き Fake観察数値 0〜0; 観察値 '0'。 |  | Optional | {"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 0 |
| charge | int | 手数料（入力） - 受注の決済手数料。paymentCharge（支払方法マスタの手数料）のスナップショット。PaymentChargePreprocessorにより受注作成時にコピーされる Fake観察数値 0〜300; 観察値 '0', '300', '200'。 |  | Optional | {"$comment":"\u624b\u6570\u6599\uff08\u5165\u529b\uff09\u306f\u672c\u6765\u6570\u5024/\u5217\u6319\u306e\u696d\u52d9\u5024\u3060\u304c\u3001HTTP\u30d5\u30a9\u30fc\u30e0\u3067\u306f\u6587\u5b57\u5217\u3068\u3057\u3066\u5c4a\u304f\u3002Resource/Semantic\u5c64\u306e400\u5fdc\u7b54\u3092\u596a\u308f\u306a\u3044\u305f\u3081transport schema\u3067\u306f\u6587\u5b57\u5217\u5165\u529b\u3092\u8a31\u5bb9\u3059\u308b\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 0 |
| usePoint | int | 使用ポイント（入力） - 注文で使用するポイント数。実際の値引き額は usePoint x pointConversionRate（切り捨て）で計算され、不課税のポイント値引き明細として受注に追加 Fake観察数値 0〜0; 観察値 '0'。 |  | Optional | {"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 0 |


### Response

[Object: PUT /admin/order response](../schemas/put-admin-order.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| orderNo | string|null | 注文番号 - 顧客向けの注文番号。フォーマットはカスタマイズ可能 Fake観察文字長 32〜32; 観察値 'past0000000000000000000000000001'。 | Required | {"minLength":0,"maxLength":64} | past0000000000000000000000000001 |
| customerId | string|null | 会員ID - dtb_customer.id の不透明な文字列ハンドル。BeMart の Entity 層は数値ではなく文字列として保持する（マスアサインメント防止のため、Session/AuthZ 経由で読み出し、リクエスト本文からは受け取らない）。Favorite / Cart / Order の所有者キーとして横断使用 Fake観察文字長 12〜32; 観察値 'customer-001', '0123456789abcdef0123456789abcdef', 'customer-002', 'favorite-list-customer', 'favorite-html-customer', 'fedcba9876543210fedcba9876543210', 'aaaaaaaa00000000bbbbbbbb11111111', '10000000aaaa1111bbbb2222cccc3333'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} | customer-001 |
| orderStatus | int | 受注ステータス - 1=新規受付, 3=注文取消, 4=対応中, 5=発送済み, 6=入金済み, 7=決済処理中, 8=購入処理中, 9=返品。Symfony Workflowステートマシンで遷移を制御。許可される遷移: pay(1->6), packing(1,6->4), cancel(1,4,6->3), back_to_in_progress(3->4), ship(1,6,4->5), return(5->9), cancel_return(9->5)。7と8はPurchaseFlow内で直接セットされステートマシン遷移の対象外 Fake観察数値 1〜1; 観察値 '1'。 | Required | {"minimum":1,"maximum":9} | 1 |
| deliveryFeeTotal | int|null | 送料合計 - 全配送先の送料合計（スナップショット）。deliveryFeeAmount（地域別送料）+ deliveryFee（商品別送料）×数量 の合計。DeliveryFeePreprocessorで計算。カートと受注の両方で使用 Fake観察数値 0〜800; 観察値 '600', '0', '500', '800', '700'。 | Required | {"minimum":0,"maximum":999999999} | 600 |
| usePoint | int|null | 使用ポイント - 注文で使用するポイント数。実際の値引き額は usePoint x pointConversionRate（切り捨て）で計算され、不課税のポイント値引き明細として受注に追加 Fake観察数値 0〜0; 観察値 '0'。 | Required | {"minimum":0,"maximum":2147483647} | 0 |
| charge | int|null | 手数料 - 受注の決済手数料。paymentCharge（支払方法マスタの手数料）のスナップショット。PaymentChargePreprocessorにより受注作成時にコピーされる Fake観察数値 0〜300; 観察値 '0', '300', '200'。 | Required | {"minimum":0,"maximum":999999999} | 0 |
| paymentTotal | int|null | 支払合計 - 実際の支払金額。初期値はtotalと同値で、PointProcessorがポイント値引きのOrderItem（type=POINT_DISCOUNT、不課税）を追加後にPurchaseFlow.calculateTotal()で再計算される。計算式: total - (利用ポイント x pointConversionRate) Fake観察数値 12700〜12700; 観察値 '12700'。 | Required | {"minimum":0,"maximum":999999999} | 12700 |
| tax | int|null | 税額 - 受注全体の税額合計（非推奨）。明細ごとの税額集計と差異が生じる場合があるため、正確な税額はOrderItem明細ごとのtaxを集計すべき Fake観察数値 1100〜1100; 観察値 '1100'。 | Required | {"minimum":0,"maximum":999999999} | 1100 |
| subtotal | int|null | 商品小計 - 商品合計金額（税込）。送料・手数料・値引き適用前の商品明細（orderItemType=1）のみの合計。PurchaseFlow.calculateSubTotal()で計算。送料無料条件の判定基準にも使用（お届け先ごとに判定） Fake観察数値 11000〜11000; 観察値 '11000'。 | Required | {"minimum":0,"maximum":999999999} | 11000 |
| discount | int|null | 値引き額 - 受注全体の値引き合計額。クーポン等による値引き Fake観察数値 0〜0; 観察値 '0'。 | Required | {"minimum":0,"maximum":999999999} | 0 |
| total | int|null | 受注合計 - 受注合計金額。計算式: subtotal(商品税込合計) + deliveryFeeTotal(送料) + charge(手数料) - discount(値引き)。カートのtotalPriceとは別プロパティ Fake観察数値 12700〜12700; 観察値 '12700'。 | Required | {"minimum":0,"maximum":999999999} | 12700 |

#### Links

| Relation | URL |
|----------|-----|
| goOrder | [<code>page://self/admin/order</code>](/admin/order.md) |