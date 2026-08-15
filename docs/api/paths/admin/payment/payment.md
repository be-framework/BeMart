<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/payment/payment
EC-CUBE doUpdatePayment + doDeletePayment — single-row endpoint
(Wave 9θ).

- GET    → goPaymentEdit (safe read, admin AUTHZ, Setting/Shop Tier-2)
- PUT    → doUpdatePayment (admin edits a payment master — idempotent)
- DELETE → doDeletePayment (admin removes a payment master — idempotent)




## GET
EC-CUBE 支払方法設定（編集） — Setting/Shop Tier-2.

Thin GET renderer for `Setting/Shop/payment_edit.twig`. An empty
`$paymentId` renders a blank "new payment" form; a known id
pre-fills the editor; an unknown id is 404. The payment-master
list doubles as the AUTHZ gate — no admin session → 403.

**ALPS**: `doUpdatePayment` - 支払方法を更新する



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| paymentId | string | 支払方法ID（入力） - dtb_payment.id の不透明な文字列ハンドル。BeMart の PaymentMethodAdminEntity 層は数値ではなく文字列として保持する。Fake 実装は 32桁hex を生成し、SQL 実装は dtb_payment.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。非数値 ID は SqlPaymentMethodAdminStorage では miss として扱われ getById / put / remove のいずれも 404 経路 (PaymentMethodAdminUpdated / PaymentMethodAdminDeleted) を踏むため、シードハンドル `nonexistent-zzz` は Fake / SQL 双方で 404 が同形。categoryId / blockId / tagId と同じ Fake↔SQL 二重性 Fake観察文字長 7〜10; Fake観察数値 1〜9; 観察値 '2', 'pay-cod', 'pay-credit', '1', '9'。 |  | Optional | {"default":"","minLength":0,"maxLength":128,"$comment":"\u652f\u6255\u65b9\u6cd5ID\uff08\u5165\u529b\uff09\u306f\u696d\u52d9\u4e0aID\u3060\u304c\u3001HTTP\u30d5\u30a9\u30fc\u30e0\u3067\u306f\u6587\u5b57\u5217\u3068\u3057\u3066\u5c4a\u304f\u3002Resource/Semantic\u5c64\u306e\u691c\u8a3c\u3092\u901a\u3059\u305f\u3081transport schema\u3067\u306fstring|integer\u3092\u8a31\u5bb9\u3059\u308b\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 2 |


### Response

[Object: GET /admin/payment/payment response](../schemas/get-admin-payment-payment.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| form | object|array|null | 入力フォーム - Aura/WebForm由来のフォームオブジェクト。フレームワーク内部構造のためschemaでは存在と型のみを契約する。 | Optional | {"$comment":"Aura/WebForm\u7531\u6765\u306e\u4e0d\u900f\u660e\u30d5\u30a9\u30fc\u30e0\u8868\u73fe\u3002Resource\u5883\u754c\u3067\u306f\u30d5\u30a9\u30fc\u30e0\u306e\u5b58\u5728\u3068\u30b3\u30f3\u30c6\u30ad\u30b9\u30c8\u3060\u3051\u3092\u5951\u7d04\u3057\u3001\u5185\u90e8\u69cb\u9020\u306f\u30d5\u30ec\u30fc\u30e0\u30ef\u30fc\u30af\u5883\u754c\u306b\u59d4\u306d\u308b\u305f\u3081\u8ffd\u52a0\u30ad\u30fc\u5236\u7d04\u3092\u7f6e\u304b\u306a\u3044\u3002"} |  |
| paymentId | string|int|null | 支払方法ID - dtb_payment.id の不透明な文字列ハンドル。BeMart の PaymentMethodAdminEntity 層は数値ではなく文字列として保持する。Fake 実装は 32桁hex を生成し、SQL 実装は dtb_payment.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。非数値 ID は SqlPaymentMethodAdminStorage では miss として扱われ getById / put / remove のいずれも 404 経路 (PaymentMethodAdminUpdated / PaymentMethodAdminDeleted) を踏むため、シードハンドル `nonexistent-zzz` は Fake / SQL 双方で 404 が同形。categoryId / blockId / tagId と同じ Fake↔SQL 二重性 Fake観察文字長 7〜10; Fake観察数値 1〜9; 観察値 '2', 'pay-cod', 'pay-credit', '1', '9'。 | Required | {"minLength":0,"maxLength":128,"$comment":"Fake\u6587\u5b57\u5217ID\u3068EC-CUBE\u6574\u6570ID\u306e\u4e21\u65b9\u304c\u89b3\u5bdf\u3055\u308c\u308b\u305f\u3081\u3001\u3053\u306e\u5883\u754c\u3060\u3051mixedBoundaryId\u3068\u3057\u3066\u6271\u3046\u3002"} | 2 |
| payment | array|null|object | 支払方法詳細 - /admin/payment/payment のレスポンスで扱う支払方法詳細。配列要素はALPS意味とFake観察に基づき、固定できない動的列は例外理由を台帳化する。 | Optional | {"items":{"type":"string","title":"\u652f\u6255\u65b9\u6cd5","minLength":0,"maxLength":255,"description":"/admin/payment/payment \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u652f\u6255\u65b9\u6cd5\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `payment` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002"},"minItems":0,"$comment":"\u5358\u4e00\u8a73\u7d30\u753b\u9762\u3067\u306f\u672a\u9078\u629e/\u521d\u671f\u8868\u793a\u306b\u7a7a\u914d\u5217\u3001\u53d6\u5f97\u6e08\u307f\u72b6\u614b\u306bobject\u304c\u73fe\u308c\u308b\u3002\u4e0d\u900f\u660e\u306a\u8a73\u7d30\u69cb\u9020\u306f\u65e2\u77e5property\u3092\u512a\u5148\u3057\u3001\u8ffd\u52a0\u30ad\u30fc\u306f\u4e92\u63db\u5883\u754c\u3068\u3057\u3066\u8a31\u5bb9\u3059\u308b\u3002"} |  |

#### Links

| Relation | URL |
|----------|-----|
| doUpdatePayment | [<code>page://self/admin/payment/payment</code>](/admin/payment/payment.md) |
## PUT
ALPS `doUpdatePayment` に対応する PUT 操作。

**ALPS**: `doUpdatePayment` - 支払方法を更新する



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| paymentId | string | 支払方法ID（入力） - dtb_payment.id の不透明な文字列ハンドル。BeMart の PaymentMethodAdminEntity 層は数値ではなく文字列として保持する。Fake 実装は 32桁hex を生成し、SQL 実装は dtb_payment.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。非数値 ID は SqlPaymentMethodAdminStorage では miss として扱われ getById / put / remove のいずれも 404 経路 (PaymentMethodAdminUpdated / PaymentMethodAdminDeleted) を踏むため、シードハンドル `nonexistent-zzz` は Fake / SQL 双方で 404 が同形。categoryId / blockId / tagId と同じ Fake↔SQL 二重性 Fake観察文字長 7〜10; Fake観察数値 1〜9; 観察値 '2', 'pay-cod', 'pay-credit', '1', '9'。 |  | Required | {"minLength":0,"maxLength":128,"$comment":"\u652f\u6255\u65b9\u6cd5ID\uff08\u5165\u529b\uff09\u306f\u696d\u52d9\u4e0aID\u3060\u304c\u3001HTTP\u30d5\u30a9\u30fc\u30e0\u3067\u306f\u6587\u5b57\u5217\u3068\u3057\u3066\u5c4a\u304f\u3002Resource/Semantic\u5c64\u306e\u691c\u8a3c\u3092\u901a\u3059\u305f\u3081transport schema\u3067\u306fstring|integer\u3092\u8a31\u5bb9\u3059\u308b\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 2 |
| paymentMethodName | string | 支払方法名（入力） - 支払方法の表示名 Fake観察文字長 4〜8; 観察値 '代金引換', 'クレジットカード', '検証失敗'。 |  | Optional | {"minLength":0,"maxLength":255,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 代金引換 |
| charge | int | 手数料（入力） - 受注の決済手数料。paymentCharge（支払方法マスタの手数料）のスナップショット。PaymentChargePreprocessorにより受注作成時にコピーされる Fake観察数値 0〜300; 観察値 '0', '300', '200'。 |  | Optional | {"$comment":"\u624b\u6570\u6599\uff08\u5165\u529b\uff09\u306f\u672c\u6765\u6570\u5024/\u5217\u6319\u306e\u696d\u52d9\u5024\u3060\u304c\u3001HTTP\u30d5\u30a9\u30fc\u30e0\u3067\u306f\u6587\u5b57\u5217\u3068\u3057\u3066\u5c4a\u304f\u3002Resource/Semantic\u5c64\u306e400\u5fdc\u7b54\u3092\u596a\u308f\u306a\u3044\u305f\u3081transport schema\u3067\u306f\u6587\u5b57\u5217\u5165\u529b\u3092\u8a31\u5bb9\u3059\u308b\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 0 |
| ruleMin | int | 下限金額（入力） - /admin/payment/payment のフォーム文脈で使う下限金額。入力保持、初期値、再表示に必要な補助値。 |  | Optional | {"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} |  |
| ruleMax | int | 上限金額（入力） - /admin/payment/payment のフォーム文脈で使う上限金額。入力保持、初期値、再表示に必要な補助値。 |  | Optional | {"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} |  |
| visible | bool | 処理状態フラグ（入力） - 観察値 'true'。 |  | Optional | {"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | true |


### Response

[Object: PUT /admin/payment/payment response](../schemas/put-admin-payment-payment.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| charge | int|null | 手数料 - 受注の決済手数料。paymentCharge（支払方法マスタの手数料）のスナップショット。PaymentChargePreprocessorにより受注作成時にコピーされる Fake観察数値 0〜300; 観察値 '0', '300', '200'。 | Required | {"minimum":0,"maximum":999999999} | 0 |
| paymentMethodName | string|null | 支払方法名 - 支払方法の表示名 Fake観察文字長 4〜8; 観察値 '代金引換', 'クレジットカード', '検証失敗'。 | Required | {"minLength":0,"maxLength":255} | 代金引換 |
| ruleMin | int|null | 下限金額 - /admin/payment/payment のフォーム文脈で使う下限金額。入力保持、初期値、再表示に必要な補助値。 | Optional | {"minimum":0,"maximum":2147483647} |  |
| ruleMax | int|null | 上限金額 - /admin/payment/payment のフォーム文脈で使う上限金額。入力保持、初期値、再表示に必要な補助値。 | Optional | {"minimum":0,"maximum":2147483647} |  |
| visible | boolean|null | 処理状態フラグ - 観察値 'true'。 | Required |  | true |
| paymentId | string|int|null | 支払方法ID - dtb_payment.id の不透明な文字列ハンドル。BeMart の PaymentMethodAdminEntity 層は数値ではなく文字列として保持する。Fake 実装は 32桁hex を生成し、SQL 実装は dtb_payment.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。非数値 ID は SqlPaymentMethodAdminStorage では miss として扱われ getById / put / remove のいずれも 404 経路 (PaymentMethodAdminUpdated / PaymentMethodAdminDeleted) を踏むため、シードハンドル `nonexistent-zzz` は Fake / SQL 双方で 404 が同形。categoryId / blockId / tagId と同じ Fake↔SQL 二重性 Fake観察文字長 7〜10; Fake観察数値 1〜9; 観察値 '2', 'pay-cod', 'pay-credit', '1', '9'。 | Required | {"minLength":0,"maxLength":128,"$comment":"Fake\u6587\u5b57\u5217ID\u3068EC-CUBE\u6574\u6570ID\u306e\u4e21\u65b9\u304c\u89b3\u5bdf\u3055\u308c\u308b\u305f\u3081\u3001\u3053\u306e\u5883\u754c\u3060\u3051mixedBoundaryId\u3068\u3057\u3066\u6271\u3046\u3002"} | 2 |

#### Links

| Relation | URL |
|----------|-----|
| goPaymentList | [<code>page://self/admin/payment/payment-list</code>](/admin/payment/payment-list.md) |
## DELETE
ALPS `doDeletePayment` に対応する DELETE 操作。

**ALPS**: `doDeletePayment` - 支払方法を削除する



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| paymentId | string | 支払方法ID（入力） - dtb_payment.id の不透明な文字列ハンドル。BeMart の PaymentMethodAdminEntity 層は数値ではなく文字列として保持する。Fake 実装は 32桁hex を生成し、SQL 実装は dtb_payment.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。非数値 ID は SqlPaymentMethodAdminStorage では miss として扱われ getById / put / remove のいずれも 404 経路 (PaymentMethodAdminUpdated / PaymentMethodAdminDeleted) を踏むため、シードハンドル `nonexistent-zzz` は Fake / SQL 双方で 404 が同形。categoryId / blockId / tagId と同じ Fake↔SQL 二重性 Fake観察文字長 7〜10; Fake観察数値 1〜9; 観察値 '2', 'pay-cod', 'pay-credit', '1', '9'。 |  | Required | {"minLength":0,"maxLength":128,"$comment":"\u652f\u6255\u65b9\u6cd5ID\uff08\u5165\u529b\uff09\u306f\u696d\u52d9\u4e0aID\u3060\u304c\u3001HTTP\u30d5\u30a9\u30fc\u30e0\u3067\u306f\u6587\u5b57\u5217\u3068\u3057\u3066\u5c4a\u304f\u3002Resource/Semantic\u5c64\u306e\u691c\u8a3c\u3092\u901a\u3059\u305f\u3081transport schema\u3067\u306fstring|integer\u3092\u8a31\u5bb9\u3059\u308b\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 2 |


### Response

[Object: DELETE /admin/payment/payment response](../schemas/delete-admin-payment-payment.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| paymentId | string|int|null | 支払方法ID - dtb_payment.id の不透明な文字列ハンドル。BeMart の PaymentMethodAdminEntity 層は数値ではなく文字列として保持する。Fake 実装は 32桁hex を生成し、SQL 実装は dtb_payment.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。非数値 ID は SqlPaymentMethodAdminStorage では miss として扱われ getById / put / remove のいずれも 404 経路 (PaymentMethodAdminUpdated / PaymentMethodAdminDeleted) を踏むため、シードハンドル `nonexistent-zzz` は Fake / SQL 双方で 404 が同形。categoryId / blockId / tagId と同じ Fake↔SQL 二重性 Fake観察文字長 7〜10; Fake観察数値 1〜9; 観察値 '2', 'pay-cod', 'pay-credit', '1', '9'。 | Required | {"minLength":0,"maxLength":128,"$comment":"Fake\u6587\u5b57\u5217ID\u3068EC-CUBE\u6574\u6570ID\u306e\u4e21\u65b9\u304c\u89b3\u5bdf\u3055\u308c\u308b\u305f\u3081\u3001\u3053\u306e\u5883\u754c\u3060\u3051mixedBoundaryId\u3068\u3057\u3066\u6271\u3046\u3002"} | 2 |

#### Links

| Relation | URL |
|----------|-----|
| goPaymentList | [<code>page://self/admin/payment/payment-list</code>](/admin/payment/payment-list.md) |
| goDeliveryList | [<code>page://self/admin/delivery/delivery-list</code>](/admin/delivery/delivery-list.md) |