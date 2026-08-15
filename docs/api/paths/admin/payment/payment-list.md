<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/payment/payment-list
EC-CUBE goPaymentList + doCreatePayment — collection endpoint
(Wave 9θ).

- GET  → goPaymentList   (admin lists payment masters — safe read)
  - POST → doCreatePayment (admin adds a new payment master)

Single-row affordances (`doUpdatePayment`, `doDeletePayment`) live
at `page://self/admin/payment/payment`.




## GET
ALPS `goPaymentList` に対応する GET 操作。

**ALPS**: `goPaymentList` - 支払方法一覧を見る



### Request

_No parameters required_

### Response

[Object: GET /admin/payment/payment-list response](../schemas/get-admin-payment-payment-list.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| payments | array|null | 支払方法一覧 - /admin/payment/payment-list のレスポンスで扱う支払方法一覧。配列要素はALPS意味とFake観察に基づき、固定できない動的列は例外理由を台帳化する。 | Required | {"items":{"type":["object","null"],"title":"\u652f\u6255\u65b9\u6cd5","description":"/admin/payment/payment-list \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u652f\u6255\u65b9\u6cd5\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `payments` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002","properties":{"charge":{"title":"\u624b\u6570\u6599","description":"\u53d7\u6ce8\u306e\u6c7a\u6e08\u624b\u6570\u6599\u3002paymentCharge\uff08\u652f\u6255\u65b9\u6cd5\u30de\u30b9\u30bf\u306e\u624b\u6570\u6599\uff09\u306e\u30b9\u30ca\u30c3\u30d7\u30b7\u30e7\u30c3\u30c8\u3002PaymentChargePreprocessor\u306b\u3088\u308a\u53d7\u6ce8\u4f5c\u6210\u6642\u306b\u30b3\u30d4\u30fc\u3055\u308c\u308b Fake\u89b3\u5bdf\u6570\u5024 0\u301c300; \u89b3\u5bdf\u5024 '0', '300', '200'\u3002","type":["integer","null"],"minimum":0,"maximum":999999999,"example":0},"paymentMethodName":{"type":["string","null"],"minLength":0,"maxLength":255,"title":"\u652f\u6255\u65b9\u6cd5\u540d","description":"\u652f\u6255\u65b9\u6cd5\u306e\u8868\u793a\u540d Fake\u89b3\u5bdf\u6587\u5b57\u9577 4\u301c8; \u89b3\u5bdf\u5024 '\u4ee3\u91d1\u5f15\u63db', '\u30af\u30ec\u30b8\u30c3\u30c8\u30ab\u30fc\u30c9', '\u691c\u8a3c\u5931\u6557'\u3002","example":"\u4ee3\u91d1\u5f15\u63db"},"ruleMin":{"type":["integer","null"],"minimum":0,"maximum":2147483647,"title":"\u4e0b\u9650\u91d1\u984d","description":"/admin/payment/payment-list \u306e\u30d5\u30a9\u30fc\u30e0\u6587\u8108\u3067\u4f7f\u3046\u4e0b\u9650\u91d1\u984d\u3002\u5165\u529b\u4fdd\u6301\u3001\u521d\u671f\u5024\u3001\u518d\u8868\u793a\u306b\u5fc5\u8981\u306a\u88dc\u52a9\u5024\u3002"},"ruleMax":{"type":["integer","null"],"minimum":0,"maximum":2147483647,"title":"\u4e0a\u9650\u91d1\u984d","description":"/admin/payment/payment-list \u306e\u30d5\u30a9\u30fc\u30e0\u6587\u8108\u3067\u4f7f\u3046\u4e0a\u9650\u91d1\u984d\u3002\u5165\u529b\u4fdd\u6301\u3001\u521d\u671f\u5024\u3001\u518d\u8868\u793a\u306b\u5fc5\u8981\u306a\u88dc\u52a9\u5024\u3002"},"visible":{"type":["boolean","null"],"title":"\u51e6\u7406\u72b6\u614b\u30d5\u30e9\u30b0","description":"\u89b3\u5bdf\u5024 'true'\u3002","example":"true"},"paymentId":{"type":["string","integer","null"],"title":"\u652f\u6255\u65b9\u6cd5ID","description":"dtb_payment.id \u306e\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217\u30cf\u30f3\u30c9\u30eb\u3002BeMart \u306e PaymentMethodAdminEntity \u5c64\u306f\u6570\u5024\u3067\u306f\u306a\u304f\u6587\u5b57\u5217\u3068\u3057\u3066\u4fdd\u6301\u3059\u308b\u3002Fake \u5b9f\u88c5\u306f 32\u6841hex \u3092\u751f\u6210\u3057\u3001SQL \u5b9f\u88c5\u306f dtb_payment.id (int unsigned AUTO_INCREMENT) \u3092\u6587\u5b57\u5217\u5316\u3057\u3066\u4f7f\u7528\uff08\u540c\u30a4\u30f3\u30bf\u30fc\u30d5\u30a7\u30a4\u30b9\u30fb\u7570 ID \u5f62\u72b6\uff09\u3002\u975e\u6570\u5024 ID \u306f SqlPaymentMethodAdminStorage \u3067\u306f miss \u3068\u3057\u3066\u6271\u308f\u308c getById / put / remove \u306e\u3044\u305a\u308c\u3082 404 \u7d4c\u8def (PaymentMethodAdminUpdated / PaymentMethodAdminDeleted) \u3092\u8e0f\u3080\u305f\u3081\u3001\u30b7\u30fc\u30c9\u30cf\u30f3\u30c9\u30eb `nonexistent-zzz` \u306f Fake / SQL \u53cc\u65b9\u3067 404 \u304c\u540c\u5f62\u3002categoryId / blockId / tagId \u3068\u540c\u3058 Fake\u2194SQL \u4e8c\u91cd\u6027 Fake\u89b3\u5bdf\u6587\u5b57\u9577 7\u301c10; Fake\u89b3\u5bdf\u6570\u5024 1\u301c9; \u89b3\u5bdf\u5024 '2', 'pay-cod', 'pay-credit', '1', '9'\u3002","example":2,"minLength":0,"maxLength":128,"$comment":"Fake\u6587\u5b57\u5217ID\u3068EC-CUBE\u6574\u6570ID\u306e\u4e21\u65b9\u304c\u89b3\u5bdf\u3055\u308c\u308b\u305f\u3081\u3001\u3053\u306e\u5883\u754c\u3060\u3051mixedBoundaryId\u3068\u3057\u3066\u6271\u3046\u3002"}},"additionalProperties":false,"$comment":"\u914d\u5217\u8981\u7d20\u306fFake/Resource\u3067\u89b3\u5bdf\u3055\u308c\u305f\u65e2\u77e5property\u306b\u56fa\u5b9a\u3059\u308b\u3002\u65b0\u3057\u3044\u5217\u304c\u5fc5\u8981\u306b\u306a\u3063\u305f\u5834\u5408\u306fSemantic-Ex\u89b3\u5bdf\u306b\u8ffd\u52a0\u3057\u3066schema\u3092\u66f4\u65b0\u3059\u308b\u3002"},"minItems":0} |  |
| count | int|null | 件数 - /admin/payment/payment-list のレスポンスで返す件数。一覧・集計・処理結果の規模を表す非負整数。 | Required | {"minimum":0,"maximum":2147483647} |  |

#### Links

| Relation | URL |
|----------|-----|
| doCreatePayment | [<code>page://self/admin/payment/payment-list</code>](/admin/payment/payment-list.md) |
| goPayment | [<code>page://self/admin/payment/payment</code>](/admin/payment/payment.md) |
| doUpdatePayment | [<code>page://self/admin/payment/payment</code>](/admin/payment/payment.md) |
| doDeletePayment | [<code>page://self/admin/payment/payment</code>](/admin/payment/payment.md) |
| goProductList | [<code>page://self/admin/product-list</code>](/admin/product-list.md) |
| goOrderList | [<code>page://self/admin/order-list</code>](/admin/order-list.md) |
## POST
ALPS `doCreatePayment` に対応する POST 操作。

**ALPS**: `doCreatePayment` - 支払方法を作成する



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| paymentMethodName | string | 支払方法名（入力） - 支払方法の表示名 Fake観察文字長 4〜8; 観察値 '代金引換', 'クレジットカード', '検証失敗'。 |  | Required | {"minLength":0,"maxLength":255,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 代金引換 |
| charge | int | 手数料（入力） - 受注の決済手数料。paymentCharge（支払方法マスタの手数料）のスナップショット。PaymentChargePreprocessorにより受注作成時にコピーされる Fake観察数値 0〜300; 観察値 '0', '300', '200'。 | 0 | Optional | {"default":0,"$comment":"\u624b\u6570\u6599\uff08\u5165\u529b\uff09\u306f\u672c\u6765\u6570\u5024/\u5217\u6319\u306e\u696d\u52d9\u5024\u3060\u304c\u3001HTTP\u30d5\u30a9\u30fc\u30e0\u3067\u306f\u6587\u5b57\u5217\u3068\u3057\u3066\u5c4a\u304f\u3002Resource/Semantic\u5c64\u306e400\u5fdc\u7b54\u3092\u596a\u308f\u306a\u3044\u305f\u3081transport schema\u3067\u306f\u6587\u5b57\u5217\u5165\u529b\u3092\u8a31\u5bb9\u3059\u308b\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 0 |
| ruleMin | int | 下限金額（入力） - /admin/payment/payment-list のフォーム文脈で使う下限金額。入力保持、初期値、再表示に必要な補助値。 |  | Optional | {"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} |  |
| ruleMax | int | 上限金額（入力） - /admin/payment/payment-list のフォーム文脈で使う上限金額。入力保持、初期値、再表示に必要な補助値。 |  | Optional | {"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} |  |
| visible | bool | 処理状態フラグ（入力） - 観察値 'true'。 | 1 | Optional | {"default":true,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | true |


### Response

[Object: POST /admin/payment/payment-list response](../schemas/post-admin-payment-payment-list.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| charge | int|null | 手数料 - 受注の決済手数料。paymentCharge（支払方法マスタの手数料）のスナップショット。PaymentChargePreprocessorにより受注作成時にコピーされる Fake観察数値 0〜300; 観察値 '0', '300', '200'。 | Required | {"minimum":0,"maximum":999999999} | 0 |
| paymentMethodName | string|null | 支払方法名 - 支払方法の表示名 Fake観察文字長 4〜8; 観察値 '代金引換', 'クレジットカード', '検証失敗'。 | Required | {"minLength":0,"maxLength":255} | 代金引換 |
| ruleMin | int|null | 下限金額 - /admin/payment/payment-list のフォーム文脈で使う下限金額。入力保持、初期値、再表示に必要な補助値。 | Optional | {"minimum":0,"maximum":2147483647} |  |
| ruleMax | int|null | 上限金額 - /admin/payment/payment-list のフォーム文脈で使う上限金額。入力保持、初期値、再表示に必要な補助値。 | Optional | {"minimum":0,"maximum":2147483647} |  |
| visible | boolean|null | 処理状態フラグ - 観察値 'true'。 | Required |  | true |
| paymentId | string|int|null | 支払方法ID - dtb_payment.id の不透明な文字列ハンドル。BeMart の PaymentMethodAdminEntity 層は数値ではなく文字列として保持する。Fake 実装は 32桁hex を生成し、SQL 実装は dtb_payment.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。非数値 ID は SqlPaymentMethodAdminStorage では miss として扱われ getById / put / remove のいずれも 404 経路 (PaymentMethodAdminUpdated / PaymentMethodAdminDeleted) を踏むため、シードハンドル `nonexistent-zzz` は Fake / SQL 双方で 404 が同形。categoryId / blockId / tagId と同じ Fake↔SQL 二重性 Fake観察文字長 7〜10; Fake観察数値 1〜9; 観察値 '2', 'pay-cod', 'pay-credit', '1', '9'。 | Required | {"minLength":0,"maxLength":128,"$comment":"Fake\u6587\u5b57\u5217ID\u3068EC-CUBE\u6574\u6570ID\u306e\u4e21\u65b9\u304c\u89b3\u5bdf\u3055\u308c\u308b\u305f\u3081\u3001\u3053\u306e\u5883\u754c\u3060\u3051mixedBoundaryId\u3068\u3057\u3066\u6271\u3046\u3002"} | 2 |

#### Links

| Relation | URL |
|----------|-----|
| goPaymentList | [<code>page://self/admin/payment/payment-list</code>](/admin/payment/payment-list.md) |