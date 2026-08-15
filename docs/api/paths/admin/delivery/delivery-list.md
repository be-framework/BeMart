<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/delivery/delivery-list
EC-CUBE goDeliveryList + doCreateDelivery — collection endpoint
(Wave 9θ).

- GET  → goDeliveryList    (admin lists delivery masters — safe read)
  - POST → doCreateDelivery  (admin adds a new delivery master)

Single-row affordances live at `page://self/admin/delivery/delivery`.




## GET
ALPS `goDeliveryList` に対応する GET 操作。

**ALPS**: `goDeliveryList` - 配送方法一覧を見る



### Request

_No parameters required_

### Response

[Object: GET /admin/delivery/delivery-list response](../schemas/get-admin-delivery-delivery-list.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| deliveries | array|null | 配送方法一覧 - /admin/delivery/delivery-list のレスポンスで扱う配送方法一覧。配列要素はALPS意味とFake観察に基づき、固定できない動的列は例外理由を台帳化する。 | Required | {"items":{"type":["object","null"],"title":"\u914d\u9001\u65b9\u6cd5","description":"/admin/delivery/delivery-list \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u914d\u9001\u65b9\u6cd5\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `deliveries` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002","properties":{"deliveryId":{"type":["string","null"],"title":"\u914d\u9001\u65b9\u6cd5ID","description":"dtb_delivery.id \u306e\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217\u30cf\u30f3\u30c9\u30eb\u3002BeMart \u306e DeliveryEntity \u5c64\u306f\u6570\u5024\u3067\u306f\u306a\u304f\u6587\u5b57\u5217\u3068\u3057\u3066\u4fdd\u6301\u3059\u308b\u3002Fake \u5b9f\u88c5\u306f 32\u6841hex \u3092\u751f\u6210\u3057\u3001SQL \u5b9f\u88c5\u306f dtb_delivery.id (int unsigned AUTO_INCREMENT) \u3092\u6587\u5b57\u5217\u5316\u3057\u3066\u4f7f\u7528\uff08\u540c\u30a4\u30f3\u30bf\u30fc\u30d5\u30a7\u30a4\u30b9\u30fb\u7570 ID \u5f62\u72b6\uff09\u3002\u975e\u6570\u5024 ID \u306f SqlDeliveryStorage \u3067\u306f miss \u3068\u3057\u3066\u6271\u308f\u308c getById / put / remove \u306e\u3044\u305a\u308c\u3082 404 \u7d4c\u8def (DeliveryUpdated / DeliveryDeleted) \u3092\u8e0f\u3080\u305f\u3081\u3001\u30b7\u30fc\u30c9\u30cf\u30f3\u30c9\u30eb `nonexistent-zzz` \u306f Fake / SQL \u53cc\u65b9\u3067 404 \u304c\u540c\u5f62\u3002blockId / pageId / categoryId \u3068\u540c\u3058 Fake\u2194SQL \u4e8c\u91cd\u6027 Fake\u89b3\u5bdf\u6587\u5b57\u9577 10\u301c10; \u89b3\u5bdf\u5024 'del-yamato', 'del-yupack'\u3002","example":"del-yamato","minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"},"deliveryName":{"type":["string","null"],"minLength":0,"maxLength":255,"title":"\u914d\u9001\u65b9\u6cd5\u540d","description":"\u7ba1\u7406\u753b\u9762\u3067\u767b\u9332\u30fb\u66f4\u65b0\u3059\u308b\u914d\u9001\u65b9\u6cd5\u306e\u8868\u793a\u540d\u3002\u5b9f\u904b\u7528\u3067\u306f\u5e97\u8217\u72ec\u81ea\u306e\u9577\u3044\u540d\u79f0\u3092\u8a31\u5bb9\u3059\u308b\u3002","example":"\u30e4\u30de\u30c8\u5b85\u6025\u4fbf"},"visible":{"type":["boolean","null"],"title":"\u51e6\u7406\u72b6\u614b\u30d5\u30e9\u30b0","description":"\u89b3\u5bdf\u5024 'true'\u3002","example":"true"}},"additionalProperties":false,"$comment":"\u914d\u5217\u8981\u7d20\u306fFake/Resource\u3067\u89b3\u5bdf\u3055\u308c\u305f\u65e2\u77e5property\u306b\u56fa\u5b9a\u3059\u308b\u3002\u65b0\u3057\u3044\u5217\u304c\u5fc5\u8981\u306b\u306a\u3063\u305f\u5834\u5408\u306fSemantic-Ex\u89b3\u5bdf\u306b\u8ffd\u52a0\u3057\u3066schema\u3092\u66f4\u65b0\u3059\u308b\u3002"},"minItems":0} |  |
| count | int|null | 件数 - /admin/delivery/delivery-list のレスポンスで返す件数。一覧・集計・処理結果の規模を表す非負整数。 | Required | {"minimum":0,"maximum":2147483647} |  |

#### Links

| Relation | URL |
|----------|-----|
| doCreateDelivery | [<code>page://self/admin/delivery/delivery-list</code>](/admin/delivery/delivery-list.md) |
| goDelivery | [<code>page://self/admin/delivery/delivery</code>](/admin/delivery/delivery.md) |
| doUpdateDelivery | [<code>page://self/admin/delivery/delivery</code>](/admin/delivery/delivery.md) |
| doDeleteDelivery | [<code>page://self/admin/delivery/delivery</code>](/admin/delivery/delivery.md) |
## POST
ALPS `doCreateDelivery` に対応する POST 操作。

**ALPS**: `doCreateDelivery` - 配送方法を作成する



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| deliveryName | string | 配送方法名 - 管理画面で登録・更新する配送方法の表示名。実運用では店舗独自の長い名称を許容する。 |  | Required | {"minLength":0,"maxLength":255,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | ヤマト宅急便 |
| visible | bool | 処理状態フラグ（入力） - 観察値 'true'。 | 1 | Optional | {"default":true,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | true |


### Response

[Object: POST /admin/delivery/delivery-list response](../schemas/post-admin-delivery-delivery-list.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| deliveryId | string|null | 配送方法ID - dtb_delivery.id の不透明な文字列ハンドル。BeMart の DeliveryEntity 層は数値ではなく文字列として保持する。Fake 実装は 32桁hex を生成し、SQL 実装は dtb_delivery.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。非数値 ID は SqlDeliveryStorage では miss として扱われ getById / put / remove のいずれも 404 経路 (DeliveryUpdated / DeliveryDeleted) を踏むため、シードハンドル `nonexistent-zzz` は Fake / SQL 双方で 404 が同形。blockId / pageId / categoryId と同じ Fake↔SQL 二重性 Fake観察文字長 10〜10; 観察値 'del-yamato', 'del-yupack'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} | del-yamato |
| deliveryName | string|null | 配送方法名 - 管理画面で登録・更新する配送方法の表示名。実運用では店舗独自の長い名称を許容する。 | Required | {"minLength":0,"maxLength":255} | ヤマト宅急便 |
| visible | boolean|null | 処理状態フラグ - 観察値 'true'。 | Required |  | true |

#### Links

| Relation | URL |
|----------|-----|
| goDeliveryList | [<code>page://self/admin/delivery/delivery-list</code>](/admin/delivery/delivery-list.md) |