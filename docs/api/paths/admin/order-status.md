<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/order-status
EC-CUBE doUpdateOrderStatus — 受注ステータス変更 (Wave 7).

POST → flip the persisted orderStatus column on one order.

Status-flip is a sub-resource of the order rather than a method on
{@see \Order} because its semantics are workflow-significant (the
change has cascade effects in EC-CUBE — cancel reverses stock /
points, ship awards points, etc. — which the Phase 2 PurchaseFlow
adapter will wire up). Surfacing a distinct URL (`/admin/order-status`)
keeps the audit story explicit and matches the ALPS-level separation
of `doUpdateOrder` vs `doUpdateOrderStatus`.

Choice of POST (not PATCH): BEAR.Sunday's natural verb set is GET /
POST / PUT / DELETE — PATCH is not first-class. POST against this
sub-resource carries the same shape as Wave 6's DeleteCustomer
(POST + CSRF + target id in body).

Failure mapping:
  - Invalid CSRF                          → 403
  - SemanticVariableException             → 400 (orderStatus format)
  - UnauthorizedAdminAccessException      → 403 (no admin session)
  - OrderNotFoundException                → 404 (unknown orderNo)

Idempotency: when the supplied `orderStatus` matches the persisted
value, the projection carries `changed=false` and the storage is
untouched. A replay returns 200 with the same body shape — mirrors
AdminCustomerDeleted's `alreadyDeleted` discipline (Wave 6).

Mass-assignment safety: only `orderNo` (target) and `orderStatus`
(new value) are accepted; no path here reaches the other dtb_order
columns.




## GET
EC-CUBE 受注対応状況設定 — Setting/Shop Tier-2.

Thin GET renderer for `Setting/Shop/order_status.twig`. BeMart
has a per-order status-change transition on POST, but not yet a
master-data transition for editing status labels/colors.

**ALPS**: `doUpdateOrderStatus`



### Request

_No parameters required_

### Response

[Object: GET /admin/order-status response](../schemas/get-admin-order-status.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| form | object|array|null | 入力フォーム - Aura/WebForm由来のフォームオブジェクト。フレームワーク内部構造のためschemaでは存在と型のみを契約する。 | Optional | {"$comment":"Aura/WebForm\u7531\u6765\u306e\u4e0d\u900f\u660e\u30d5\u30a9\u30fc\u30e0\u8868\u73fe\u3002Resource\u5883\u754c\u3067\u306f\u30d5\u30a9\u30fc\u30e0\u306e\u5b58\u5728\u3068\u30b3\u30f3\u30c6\u30ad\u30b9\u30c8\u3060\u3051\u3092\u5951\u7d04\u3057\u3001\u5185\u90e8\u69cb\u9020\u306f\u30d5\u30ec\u30fc\u30e0\u30ef\u30fc\u30af\u5883\u754c\u306b\u59d4\u306d\u308b\u305f\u3081\u8ffd\u52a0\u30ad\u30fc\u5236\u7d04\u3092\u7f6e\u304b\u306a\u3044\u3002"} |  |
| orderStatuses | array|null | 注文ステータス一覧 - /admin/order-status のレスポンスで扱う注文ステータス一覧。配列要素はALPS意味とFake観察に基づき、固定できない動的列は例外理由を台帳化する。 | Required | {"items":{"type":["object","null"],"title":"\u6ce8\u6587\u30b9\u30c6\u30fc\u30bf\u30b9","description":"/admin/order-status \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u6ce8\u6587\u30b9\u30c6\u30fc\u30bf\u30b9\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `orderStatuses` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002","properties":{"displayOrderCountKey":{"type":["string","null"],"title":"\u8868\u793a\u9806\u4ef6\u6570\u30ad\u30fc","description":"/admin/order-status \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u3067\u6271\u3046\u8868\u793a\u9806\u4ef6\u6570\u30ad\u30fc\u3002\u6570\u5024\u6f14\u7b97\u5bfe\u8c61\u3067\u306f\u306a\u304f\u3001\u7167\u5408\u30fbURL\u30fb\u914d\u9001\u8ffd\u8de1\u306a\u3069\u306b\u4f7f\u3046\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217\u8b58\u5225\u5b50\u3002","minLength":0,"maxLength":128,"$comment":"\u30ad\u30fc/\u8ffd\u8de1\u756a\u53f7\u306f\u7167\u5408\u7528\u306e\u4e0d\u900f\u660e\u6587\u5b57\u5217\u3067\u3001\u6570\u5024\u6f14\u7b97\u5bfe\u8c61\u3067\u306f\u306a\u3044\u3002"},"customerNameKey":{"type":["string","null"],"minLength":0,"maxLength":128,"title":"\u9867\u5ba2\u540d\u30ad\u30fc","description":"/admin/order-status \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u3067\u6271\u3046\u9867\u5ba2\u540d\u30ad\u30fc\u3002\u6570\u5024\u6f14\u7b97\u5bfe\u8c61\u3067\u306f\u306a\u304f\u3001\u7167\u5408\u30fbURL\u30fb\u914d\u9001\u8ffd\u8de1\u306a\u3069\u306b\u4f7f\u3046\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217\u8b58\u5225\u5b50\u3002","$comment":"\u30ad\u30fc/\u8ffd\u8de1\u756a\u53f7\u306f\u7167\u5408\u7528\u306e\u4e0d\u900f\u660e\u6587\u5b57\u5217\u3067\u3001\u6570\u5024\u6f14\u7b97\u5bfe\u8c61\u3067\u306f\u306a\u3044\u3002"},"id":{"type":["string","integer","null"],"title":"ID","description":"Fake\u89b3\u5bdf\u6587\u5b57\u9577 13\u301c32; \u89b3\u5bdf\u5024 'ad000000000000000000000000000001', 'ad000000000000000000000000000003', 'fedcba9876543210fedcba9876543210', '10000000aaaa1111bbbb2222cccc3333', 'ad000000000000000000000000000002', '0123456789abcdef0123456789abcdef', 'aaaaaaaa00000000bbbbbbbb11111111', '20000000dddd2222eeee3333ffff4444'\u3002","example":"ad000000000000000000000000000001","minLength":0,"maxLength":128,"$comment":"Fake\u6587\u5b57\u5217ID\u3068EC-CUBE\u6574\u6570ID\u306e\u4e21\u65b9\u304c\u89b3\u5bdf\u3055\u308c\u308b\u305f\u3081\u3001\u3053\u306e\u5883\u754c\u3060\u3051mixedBoundaryId\u3068\u3057\u3066\u6271\u3046\u3002"},"nameKey":{"type":["string","null"],"minLength":0,"maxLength":128,"title":"\u540d\u79f0\u30ad\u30fc","description":"/admin/order-status \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u3067\u6271\u3046\u540d\u79f0\u30ad\u30fc\u3002\u6570\u5024\u6f14\u7b97\u5bfe\u8c61\u3067\u306f\u306a\u304f\u3001\u7167\u5408\u30fbURL\u30fb\u914d\u9001\u8ffd\u8de1\u306a\u3069\u306b\u4f7f\u3046\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217\u8b58\u5225\u5b50\u3002","$comment":"\u30ad\u30fc/\u8ffd\u8de1\u756a\u53f7\u306f\u7167\u5408\u7528\u306e\u4e0d\u900f\u660e\u6587\u5b57\u5217\u3067\u3001\u6570\u5024\u6f14\u7b97\u5bfe\u8c61\u3067\u306f\u306a\u3044\u3002"},"colorKey":{"type":["string","null"],"minLength":0,"maxLength":128,"title":"\u8272\u30ad\u30fc","description":"/admin/order-status \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u3067\u6271\u3046\u8272\u30ad\u30fc\u3002\u6570\u5024\u6f14\u7b97\u5bfe\u8c61\u3067\u306f\u306a\u304f\u3001\u7167\u5408\u30fbURL\u30fb\u914d\u9001\u8ffd\u8de1\u306a\u3069\u306b\u4f7f\u3046\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217\u8b58\u5225\u5b50\u3002","$comment":"\u30ad\u30fc/\u8ffd\u8de1\u756a\u53f7\u306f\u7167\u5408\u7528\u306e\u4e0d\u900f\u660e\u6587\u5b57\u5217\u3067\u3001\u6570\u5024\u6f14\u7b97\u5bfe\u8c61\u3067\u306f\u306a\u3044\u3002"}},"$comment":"\u914d\u5217\u8981\u7d20\u306f\u30de\u30b9\u30bf/CSV/option map\u306e\u52d5\u7684\u884c\u3067\u3042\u308a\u3001\u5217\u96c6\u5408\u304c\u5bfe\u8c61\u7a2e\u5225\u306b\u3088\u308a\u5909\u308f\u308b\u305f\u3081\u56fa\u5b9ashape\u5316\u3057\u306a\u3044\u3002\u65e2\u77e5property\u306f\u5951\u7d04\u3057\u3001\u8ffd\u52a0\u5217\u306f\u8a72\u5f53\u30b5\u30fc\u30d3\u30b9\u5883\u754c\u3067\u6271\u3046\u3002"},"minItems":0} |  |
| csrfToken | string|null | CSRFトークン - POST /admin/order-status のhidden inputで送るCSRFトークン。 | Required | {"minLength":0,"maxLength":160,"pattern":"^[A-Za-z0-9_.:-]*$"} |  |

#### Links

| Relation | URL |
|----------|-----|
| doUpdateOrderStatusList | [<code>page://self/admin/order-status</code>](/admin/order-status.md) |
## PUT
EC-CUBE doUpdateOrderStatusList — settings-side status list update.

This is intentionally separate from {@see \onPost()}, which
updates one order's workflow status. The settings screen submits
the master-list shape; this wave exposes a concrete CSRF/AUTHZ
surface and returns the accepted payload count without claiming
full EC-CUBE master-data persistence yet.

**ALPS**: `doUpdateOrderStatusList`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| orderStatuses | array | 注文ステータス一覧（入力） - /admin/order-status のレスポンスで扱う注文ステータス一覧。配列要素はALPS意味とFake観察に基づき、固定できない動的列は例外理由を台帳化する。 | array () | Optional | {"items":{"type":"object","title":"\u6ce8\u6587\u30b9\u30c6\u30fc\u30bf\u30b9\uff08\u5165\u529b\uff09","description":"/admin/order-status \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u6ce8\u6587\u30b9\u30c6\u30fc\u30bf\u30b9\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `orderStatuses` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002","properties":{"name":{"type":["string","null"],"minLength":0,"maxLength":32,"title":"\u51e6\u7406\u8868\u793a\u540d\uff08\u5165\u529b\uff09","description":"Fake\u89b3\u5bdf\u6587\u5b57\u9577 1\u301c7; \u89b3\u5bdf\u5024 '\u30c6\u30b9\u30c8\u7ba1\u7406\u8005', '\u526f\u7ba1\u7406\u8005', '\u5e97\u8217\u30aa\u30fc\u30ca\u30fc', '\u524a\u9664\u6e08\u307f\u7ba1\u7406\u8005', 'Red', 'Blue', 'S', 'Color'\u3002","example":"\u30c6\u30b9\u30c8\u7ba1\u7406\u8005","$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."},"color":{"type":["string","null"],"minLength":0,"maxLength":255,"title":"\u8868\u793a\u9805\u76ee\uff08\u5165\u529b\uff09","description":"/admin/order-status \u306e\u753b\u9762\u8868\u793a\u306b\u4f7f\u3046\u8868\u793a\u9805\u76ee\u3002\u696d\u52d9\u30a8\u30f3\u30c6\u30a3\u30c6\u30a3\u305d\u306e\u3082\u306e\u3067\u306f\u306a\u304f\u30c6\u30f3\u30d7\u30ec\u30fc\u30c8/\u4e00\u89a7\u8868\u793a\u306e\u88dc\u52a9\u5024\u3002","$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."},"count":{"type":["integer","null"],"title":"\u4ef6\u6570","description":"/admin/order-status \u306e\u5165\u529b\u3067\u8fd4\u3059\u4ef6\u6570\u3002\u4e00\u89a7\u30fb\u96c6\u8a08\u30fb\u51e6\u7406\u7d50\u679c\u306e\u898f\u6a21\u3092\u8868\u3059\u975e\u8ca0\u6574\u6570\u3002","$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."}},"$comment":"\u914d\u5217\u8981\u7d20\u306f\u30de\u30b9\u30bf/CSV/option map\u306e\u52d5\u7684\u884c\u3067\u3042\u308a\u3001\u5217\u96c6\u5408\u304c\u5bfe\u8c61\u7a2e\u5225\u306b\u3088\u308a\u5909\u308f\u308b\u305f\u3081\u56fa\u5b9ashape\u5316\u3057\u306a\u3044\u3002\u65e2\u77e5property\u306f\u5951\u7d04\u3057\u3001\u8ffd\u52a0\u5217\u306f\u8a72\u5f53\u30b5\u30fc\u30d3\u30b9\u5883\u754c\u3067\u6271\u3046\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."},"minItems":0,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} |  |
| orderStatusRows | string | 受注ステータス（入力） - 1=新規受付, 3=注文取消, 4=対応中, 5=発送済み, 6=入金済み, 7=決済処理中, 8=購入処理中, 9=返品。Symfony Workflowステートマシンで遷移を制御。許可される遷移: pay(1->6), packing(1,6->4), cancel(1,4,6->3), back_to_in_progress(3->4), ship(1,6,4->5), return(5->9), cancel_return(9->5)。7と8はPurchaseFlow内で直接セットされステートマシン遷移の対象外 |  | Optional | {"minLength":0,"maxLength":255,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} |  |


### Response

[Object: PUT /admin/order-status response](../schemas/put-admin-order-status.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| orderStatusRows | string|null | 受注ステータス - 1=新規受付, 3=注文取消, 4=対応中, 5=発送済み, 6=入金済み, 7=決済処理中, 8=購入処理中, 9=返品。Symfony Workflowステートマシンで遷移を制御。許可される遷移: pay(1->6), packing(1,6->4), cancel(1,4,6->3), back_to_in_progress(3->4), ship(1,6,4->5), return(5->9), cancel_return(9->5)。7と8はPurchaseFlow内で直接セットされステートマシン遷移の対象外 | Required | {"minLength":0,"maxLength":255} |  |
| message | string|null | 注文メッセージ - /admin/order-status のレスポンスに含まれる処理結果メッセージ。注文時お問い合わせ欄ではなく、画面遷移や完了表示のための通知文。 | Optional | {"minLength":0,"maxLength":32} | 配送は平日希望です。 |
| count | int|null | 件数 - /admin/order-status のレスポンスで返す件数。一覧・集計・処理結果の規模を表す非負整数。 | Required | {"minimum":0,"maximum":2147483647} |  |
| transitionId | string | ALPS遷移ID - このレスポンス/操作が対応するALPS遷移ID。クライアントの状態遷移追跡に使う。 | Required | {"minLength":2,"maxLength":96,"pattern":"^(go|do)[A-Z][A-Za-z0-9]*$"} | doAddCartItem |

## POST
Wave 7: both `orderNo` and `orderStatus` are admin-form input
(orderNo selected from the order-list row, orderStatus picked
from a dropdown of dtb_order_status values).

**ALPS**: `doUpdateOrderStatus`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| orderNo | string | 注文番号（入力） - 顧客向けの注文番号。フォーマットはカスタマイズ可能 Fake観察文字長 32〜32; 観察値 'past0000000000000000000000000001'。 |  | Required | {"minLength":0,"maxLength":64,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | past0000000000000000000000000001 |
| orderStatus | int | 受注ステータス（入力） - 1=新規受付, 3=注文取消, 4=対応中, 5=発送済み, 6=入金済み, 7=決済処理中, 8=購入処理中, 9=返品。Symfony Workflowステートマシンで遷移を制御。許可される遷移: pay(1->6), packing(1,6->4), cancel(1,4,6->3), back_to_in_progress(3->4), ship(1,6,4->5), return(5->9), cancel_return(9->5)。7と8はPurchaseFlow内で直接セットされステートマシン遷移の対象外 Fake観察数値 1〜1; 観察値 '1'。 |  | Required | {"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 1 |


### Response

[Object: POST /admin/order-status response](../schemas/post-admin-order-status.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| changed | boolean|null | 処理状態フラグ - Fake観察数値 1〜1; 観察値 '1'。 | Required |  | 1 |
| previousStatus | int | 処理一覧 - /admin/order-status のレスポンスで扱う処理一覧。ALPS、Fake観察、Resource境界の形状から導いた業務契約。 | Required | {"minimum":1,"maximum":9} | 1 |
| orderNo | string|null | 注文番号 - 顧客向けの注文番号。フォーマットはカスタマイズ可能 Fake観察文字長 32〜32; 観察値 'past0000000000000000000000000001'。 | Required | {"minLength":0,"maxLength":64} | past0000000000000000000000000001 |
| orderStatus | int | 受注ステータス - 1=新規受付, 3=注文取消, 4=対応中, 5=発送済み, 6=入金済み, 7=決済処理中, 8=購入処理中, 9=返品。Symfony Workflowステートマシンで遷移を制御。許可される遷移: pay(1->6), packing(1,6->4), cancel(1,4,6->3), back_to_in_progress(3->4), ship(1,6,4->5), return(5->9), cancel_return(9->5)。7と8はPurchaseFlow内で直接セットされステートマシン遷移の対象外 Fake観察数値 1〜1; 観察値 '1'。 | Required | {"minimum":1,"maximum":9} | 1 |

#### Links

| Relation | URL |
|----------|-----|
| goOrder | [<code>page://self/admin/order</code>](/admin/order.md) |
| goOrderList | [<code>page://self/admin/order-list</code>](/admin/order-list.md) |
| goOrderShippingAddress | [<code>page://self/admin/order/shipping-address</code>](/admin/order/shipping-address.md) |