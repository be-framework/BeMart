<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/delivery/delivery
EC-CUBE doUpdateDelivery + doDeleteDelivery — single-row endpoint
(Wave 9θ).

- GET    → goDeliveryEdit (safe read, admin AUTHZ, Setting/Shop Tier-2)
- PUT    → doUpdateDelivery (admin edits a delivery master — idempotent)
- DELETE → doDeleteDelivery (admin removes a delivery master — idempotent)




## GET
EC-CUBE 配送方法設定（編集） — Setting/Shop Tier-2.

Thin GET renderer for `Setting/Shop/delivery_edit.twig`. An empty
`$deliveryId` renders a blank "new delivery" form; a known id
pre-fills the editor; an unknown id is 404. The delivery-master
list doubles as the AUTHZ gate — no admin session → 403.

**ALPS**: `doUpdateDelivery`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| deliveryId | string | 配送方法ID（入力） - dtb_delivery.id の不透明な文字列ハンドル。BeMart の DeliveryEntity 層は数値ではなく文字列として保持する。Fake 実装は 32桁hex を生成し、SQL 実装は dtb_delivery.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。非数値 ID は SqlDeliveryStorage では miss として扱われ getById / put / remove のいずれも 404 経路 (DeliveryUpdated / DeliveryDeleted) を踏むため、シードハンドル `nonexistent-zzz` は Fake / SQL 双方で 404 が同形。blockId / pageId / categoryId と同じ Fake↔SQL 二重性 Fake観察文字長 10〜10; 観察値 'del-yamato', 'del-yupack'。 |  | Optional | {"default":"","minLength":0,"maxLength":128,"$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | del-yamato |


### Response

[Object: GET /admin/delivery/delivery response](../schemas/get-admin-delivery-delivery.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| deliveryId | string|null | 配送方法ID - dtb_delivery.id の不透明な文字列ハンドル。BeMart の DeliveryEntity 層は数値ではなく文字列として保持する。Fake 実装は 32桁hex を生成し、SQL 実装は dtb_delivery.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。非数値 ID は SqlDeliveryStorage では miss として扱われ getById / put / remove のいずれも 404 経路 (DeliveryUpdated / DeliveryDeleted) を踏むため、シードハンドル `nonexistent-zzz` は Fake / SQL 双方で 404 が同形。blockId / pageId / categoryId と同じ Fake↔SQL 二重性 Fake観察文字長 10〜10; 観察値 'del-yamato', 'del-yupack'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} | del-yamato |
| form | object|array|null | 入力フォーム - Aura/WebForm由来のフォームオブジェクト。フレームワーク内部構造のためschemaでは存在と型のみを契約する。 | Optional | {"$comment":"Aura/WebForm\u7531\u6765\u306e\u4e0d\u900f\u660e\u30d5\u30a9\u30fc\u30e0\u8868\u73fe\u3002Resource\u5883\u754c\u3067\u306f\u30d5\u30a9\u30fc\u30e0\u306e\u5b58\u5728\u3068\u30b3\u30f3\u30c6\u30ad\u30b9\u30c8\u3060\u3051\u3092\u5951\u7d04\u3057\u3001\u5185\u90e8\u69cb\u9020\u306f\u30d5\u30ec\u30fc\u30e0\u30ef\u30fc\u30af\u5883\u754c\u306b\u59d4\u306d\u308b\u305f\u3081\u8ffd\u52a0\u30ad\u30fc\u5236\u7d04\u3092\u7f6e\u304b\u306a\u3044\u3002"} |  |
| delivery | array|null|object | 配送方法詳細 - /admin/delivery/delivery のレスポンスで扱う配送方法詳細。配列要素はALPS意味とFake観察に基づき、固定できない動的列は例外理由を台帳化する。 | Optional | {"items":{"type":"string","title":"\u914d\u9001\u65b9\u6cd5","minLength":0,"maxLength":255,"description":"/admin/delivery/delivery \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u914d\u9001\u65b9\u6cd5\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `delivery` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002"},"minItems":0,"$comment":"\u5358\u4e00\u8a73\u7d30\u753b\u9762\u3067\u306f\u672a\u9078\u629e/\u521d\u671f\u8868\u793a\u306b\u7a7a\u914d\u5217\u3001\u53d6\u5f97\u6e08\u307f\u72b6\u614b\u306bobject\u304c\u73fe\u308c\u308b\u3002\u4e0d\u900f\u660e\u306a\u8a73\u7d30\u69cb\u9020\u306f\u65e2\u77e5property\u3092\u512a\u5148\u3057\u3001\u8ffd\u52a0\u30ad\u30fc\u306f\u4e92\u63db\u5883\u754c\u3068\u3057\u3066\u8a31\u5bb9\u3059\u308b\u3002"} |  |

#### Links

| Relation | URL |
|----------|-----|
| doUpdateDelivery | [<code>page://self/admin/delivery/delivery</code>](/admin/delivery/delivery.md) |
## PUT
ALPS `doUpdateDelivery` に対応する PUT 操作。

**ALPS**: `doUpdateDelivery`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| deliveryId | string | 配送方法ID（入力） - dtb_delivery.id の不透明な文字列ハンドル。BeMart の DeliveryEntity 層は数値ではなく文字列として保持する。Fake 実装は 32桁hex を生成し、SQL 実装は dtb_delivery.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。非数値 ID は SqlDeliveryStorage では miss として扱われ getById / put / remove のいずれも 404 経路 (DeliveryUpdated / DeliveryDeleted) を踏むため、シードハンドル `nonexistent-zzz` は Fake / SQL 双方で 404 が同形。blockId / pageId / categoryId と同じ Fake↔SQL 二重性 Fake観察文字長 10〜10; 観察値 'del-yamato', 'del-yupack'。 |  | Required | {"minLength":0,"maxLength":128,"$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | del-yamato |
| deliveryName | string | 配送方法名 - 管理画面で登録・更新する配送方法の表示名。実運用では店舗独自の長い名称を許容する。 |  | Optional | {"minLength":0,"maxLength":255,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | ヤマト宅急便 |
| visible | bool | 処理状態フラグ（入力） - 観察値 'true'。 |  | Optional | {"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | true |


### Response

[Object: PUT /admin/delivery/delivery response](../schemas/put-admin-delivery-delivery.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| deliveryId | string|null | 配送方法ID - dtb_delivery.id の不透明な文字列ハンドル。BeMart の DeliveryEntity 層は数値ではなく文字列として保持する。Fake 実装は 32桁hex を生成し、SQL 実装は dtb_delivery.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。非数値 ID は SqlDeliveryStorage では miss として扱われ getById / put / remove のいずれも 404 経路 (DeliveryUpdated / DeliveryDeleted) を踏むため、シードハンドル `nonexistent-zzz` は Fake / SQL 双方で 404 が同形。blockId / pageId / categoryId と同じ Fake↔SQL 二重性 Fake観察文字長 10〜10; 観察値 'del-yamato', 'del-yupack'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} | del-yamato |
| deliveryName | string|null | 配送方法名 - 管理画面で登録・更新する配送方法の表示名。実運用では店舗独自の長い名称を許容する。 | Required | {"minLength":0,"maxLength":255} | ヤマト宅急便 |
| visible | boolean|null | 処理状態フラグ - 観察値 'true'。 | Required |  | true |

#### Links

| Relation | URL |
|----------|-----|
| goDeliveryList | [<code>page://self/admin/delivery/delivery-list</code>](/admin/delivery/delivery-list.md) |
## DELETE
ALPS `doUpdateDelivery` に対応する DELETE 操作。

**ALPS**: `doUpdateDelivery`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| deliveryId | string | 配送方法ID（入力） - dtb_delivery.id の不透明な文字列ハンドル。BeMart の DeliveryEntity 層は数値ではなく文字列として保持する。Fake 実装は 32桁hex を生成し、SQL 実装は dtb_delivery.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。非数値 ID は SqlDeliveryStorage では miss として扱われ getById / put / remove のいずれも 404 経路 (DeliveryUpdated / DeliveryDeleted) を踏むため、シードハンドル `nonexistent-zzz` は Fake / SQL 双方で 404 が同形。blockId / pageId / categoryId と同じ Fake↔SQL 二重性 Fake観察文字長 10〜10; 観察値 'del-yamato', 'del-yupack'。 |  | Required | {"minLength":0,"maxLength":128,"$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | del-yamato |


### Response

[Object: DELETE /admin/delivery/delivery response](../schemas/delete-admin-delivery-delivery.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| deliveryId | string|null | 配送方法ID - dtb_delivery.id の不透明な文字列ハンドル。BeMart の DeliveryEntity 層は数値ではなく文字列として保持する。Fake 実装は 32桁hex を生成し、SQL 実装は dtb_delivery.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。非数値 ID は SqlDeliveryStorage では miss として扱われ getById / put / remove のいずれも 404 経路 (DeliveryUpdated / DeliveryDeleted) を踏むため、シードハンドル `nonexistent-zzz` は Fake / SQL 双方で 404 が同形。blockId / pageId / categoryId と同じ Fake↔SQL 二重性 Fake観察文字長 10〜10; 観察値 'del-yamato', 'del-yupack'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} | del-yamato |

#### Links

| Relation | URL |
|----------|-----|
| goDeliveryList | [<code>page://self/admin/delivery/delivery-list</code>](/admin/delivery/delivery-list.md) |
| goTaxRuleList | [<code>page://self/admin/tax-rule/tax-rule-list</code>](/admin/tax-rule/tax-rule-list.md) |