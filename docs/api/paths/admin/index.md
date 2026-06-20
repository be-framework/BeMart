<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/index
EC-CUBE admin home — 管理画面ダッシュボード (top-level wave, Phase 3).

Renderer for the admin dashboard (`admin/index.twig`). EC-CUBE's
dashboard is a controller-assembled aggregate of KPIs — order-status
counts, weekly/monthly/yearly sales charts, shop-status counters
(out-of-stock / product / customer totals) and a recommended-plugins
panel.

The 「ショップ状況」 counters (取扱商品数 / 会員数 / 在庫切れ商品数) ARE
wired to a real projection: {@see \DashboardCountsQueryInterface} reads
them in one query over the product / customer / product-class storages.
Counting registered rows is not inventing data, so these are surfaced
honestly.

The remaining widgets — `orderStatuses`, `orders` (per-status counts),
`salesThisMonth` / `salesToday` / `salesYesterday` and
`recommendedPlugins` — have no Be Framework projection yet (no
`goDashboard` transition / sales-aggregate Entity in `alps.json`), so
the body still carries safe empties for them and the HTML port renders
the EC-CUBE skeleton verbatim around those.




## GET
Renders the admin dashboard scaffolding.

Admin-only: returns 403 for an anonymous (not-logged-in-as-admin)
request — the same firewall contract as the News / Customer admin
pages, enforced here at the resource layer because there is no Be
Final to raise `UnauthorizedAdminAccessException`.

**ALPS**: `goAdminTop`



### Request

_No parameters required_

### Response

[Object: GET /admin/index response](../schemas/get-admin-index.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| countNonStockProducts | int|null | 在庫切れ商品数 - /admin/index のレスポンスで返す在庫切れ商品数。一覧、集計、CSV処理結果の規模を表す非負の数値。 | Required | {"minimum":0,"maximum":2147483647} |  |
| salesToday | int|null | 本日売上 - /admin/index のレスポンスで返す本日売上。一覧、集計、CSV処理結果の規模を表す非負の数値。 | Required | {"minimum":0,"maximum":999999999} | 1200 |
| recommendedPlugins | array|null | 推奨プラグイン一覧 - /admin/index のレスポンスで扱う推奨プラグイン一覧。配列要素はALPS意味とFake観察に基づき、固定できない動的列は例外理由を台帳化する。 | Required | {"items":{"type":"object","title":"\u63a8\u5968\u30d7\u30e9\u30b0\u30a4\u30f3","description":"/admin/index \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u63a8\u5968\u30d7\u30e9\u30b0\u30a4\u30f3\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `recommendedPlugins` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002","properties":{"pluginCode":{"type":["string","null"],"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/+-]*$","title":"\u30d7\u30e9\u30b0\u30a4\u30f3\u30b3\u30fc\u30c9","description":"\u30d7\u30e9\u30b0\u30a4\u30f3\u306e\u4e00\u610f\u8b58\u5225\u5b50\u3002dtb_plugin.code \u306b\u683c\u7d0d\u3059\u308b\u81ea\u7136\u30ad\u30fc \u2014 \u5217\u540d\u306f `code` \u3067\u3042\u3063\u3066 `plugin_code` \u3067\u306f\u306a\u3044\uff08dtb_plugin \u306f EC-CUBE \u5f8c\u767a\u306e dtb_*_code \u547d\u540d\u898f\u7d04\u3088\u308a\u53e4\u3044\uff09\u3002findByCode / install / uninstall / setEnabled \u306e\u5168\u30e9\u30a4\u30d5\u30b5\u30a4\u30af\u30eb\u30e1\u30bd\u30c3\u30c9\u304c\u3053\u306e\u5217\u3092\u30d7\u30ed\u30fc\u30d6\u3059\u308b\u3002dtb_plugin \u306f FK \u5236\u7d04\u3092\u6301\u305f\u306a\u3044\u304c structure-only \u30c0\u30f3\u30d7\u3067\u306f\u7a7a\u306e\u305f\u3081\u3001SQL \u30cf\u30a4\u30d1\u30fc\u30e1\u30c7\u30a3\u30a2\u30c6\u30b9\u30c8\u306f seedPlugins \u30672\u3064\u306e\u30c7\u30e2\u30d7\u30e9\u30b0\u30a4\u30f3\uff08Sample/SamplePlugin, Sample/DisabledPlugin\uff09\u3092\u30b7\u30fc\u30c9\u3059\u308b Fake\u89b3\u5bdf\u6587\u5b57\u9577 19\u301c21; \u89b3\u5bdf\u5024 'Sample/SamplePlugin', 'Sample/DisabledPlugin'\u3002","example":"Sample/SamplePlugin"},"name":{"type":["string","null"],"minLength":0,"maxLength":32,"title":"\u51e6\u7406\u8868\u793a\u540d","description":"Fake\u89b3\u5bdf\u6587\u5b57\u9577 1\u301c7; \u89b3\u5bdf\u5024 '\u30c6\u30b9\u30c8\u7ba1\u7406\u8005', '\u526f\u7ba1\u7406\u8005', '\u5e97\u8217\u30aa\u30fc\u30ca\u30fc', '\u524a\u9664\u6e08\u307f\u7ba1\u7406\u8005', 'Red', 'Blue', 'S', 'Color'\u3002","example":"\u30c6\u30b9\u30c8\u7ba1\u7406\u8005"},"version":{"type":["string","null"],"minLength":0,"maxLength":32,"title":"\u8868\u793a\u9805\u76ee","description":"Fake\u89b3\u5bdf\u6587\u5b57\u9577 5\u301c5; \u89b3\u5bdf\u5024 '1.0.0'\u3002","example":"1.0.0"},"enabled":{"type":["boolean","null"],"title":"\u51e6\u7406\u72b6\u614b\u30d5\u30e9\u30b0","description":"\u89b3\u5bdf\u5024 'true', 'false'\u3002","example":"true"}},"$comment":"\u914d\u5217\u8981\u7d20\u306f\u30de\u30b9\u30bf/CSV/option map\u306e\u52d5\u7684\u884c\u3067\u3042\u308a\u3001\u5217\u96c6\u5408\u304c\u5bfe\u8c61\u7a2e\u5225\u306b\u3088\u308a\u5909\u308f\u308b\u305f\u3081\u56fa\u5b9ashape\u5316\u3057\u306a\u3044\u3002\u65e2\u77e5property\u306f\u5951\u7d04\u3057\u3001\u8ffd\u52a0\u5217\u306f\u8a72\u5f53\u30b5\u30fc\u30d3\u30b9\u5883\u754c\u3067\u6271\u3046\u3002"},"minItems":0} |  |
| orders | array|null | 注文一覧 - /admin/index のレスポンスで扱う注文一覧。配列要素はALPS意味とFake観察に基づき、固定できない動的列は例外理由を台帳化する。 | Required | {"items":{"type":"object","title":"\u6ce8\u6587\u6982\u8981","description":"/admin/index \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u6ce8\u6587\u6982\u8981\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `orders` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002","properties":{"orderNo":{"type":["string","null"],"minLength":0,"maxLength":64,"title":"\u6ce8\u6587\u756a\u53f7","description":"\u9867\u5ba2\u5411\u3051\u306e\u6ce8\u6587\u756a\u53f7\u3002\u30d5\u30a9\u30fc\u30de\u30c3\u30c8\u306f\u30ab\u30b9\u30bf\u30de\u30a4\u30ba\u53ef\u80fd Fake\u89b3\u5bdf\u6587\u5b57\u9577 32\u301c32; \u89b3\u5bdf\u5024 'past0000000000000000000000000001'\u3002","example":"past0000000000000000000000000001"},"orderStatus":{"title":"\u53d7\u6ce8\u30b9\u30c6\u30fc\u30bf\u30b9","description":"1=\u65b0\u898f\u53d7\u4ed8, 3=\u6ce8\u6587\u53d6\u6d88, 4=\u5bfe\u5fdc\u4e2d, 5=\u767a\u9001\u6e08\u307f, 6=\u5165\u91d1\u6e08\u307f, 7=\u6c7a\u6e08\u51e6\u7406\u4e2d, 8=\u8cfc\u5165\u51e6\u7406\u4e2d, 9=\u8fd4\u54c1\u3002Symfony Workflow\u30b9\u30c6\u30fc\u30c8\u30de\u30b7\u30f3\u3067\u9077\u79fb\u3092\u5236\u5fa1\u3002\u8a31\u53ef\u3055\u308c\u308b\u9077\u79fb: pay(1->6), packing(1,6->4), cancel(1,4,6->3), back_to_in_progress(3->4), ship(1,6,4->5), return(5->9), cancel_return(9->5)\u30027\u30688\u306fPurchaseFlow\u5185\u3067\u76f4\u63a5\u30bb\u30c3\u30c8\u3055\u308c\u30b9\u30c6\u30fc\u30c8\u30de\u30b7\u30f3\u9077\u79fb\u306e\u5bfe\u8c61\u5916 Fake\u89b3\u5bdf\u6570\u5024 1\u301c1; \u89b3\u5bdf\u5024 '1'\u3002","type":["integer","null"],"minimum":1,"maximum":9,"example":1},"orderDate":{"title":"\u6ce8\u6587\u65e5","description":"\u6ce8\u6587\u78ba\u5b9a\u65e5\u6642 Fake\u89b3\u5bdf\u6587\u5b57\u9577 19\u301c19; \u89b3\u5bdf\u5024 '2026-04-01 10:00:00'\u3002","type":["string","null"],"example":"2026-04-01 10:00:00","pattern":"^\\d{4}-\\d{2}-\\d{2}([ T]\\d{2}:\\d{2}:\\d{2}([+-]\\d{2}:?\\d{2}|Z)?)?$"},"paymentTotal":{"type":["integer","null"],"title":"\u652f\u6255\u5408\u8a08","description":"\u5b9f\u969b\u306e\u652f\u6255\u91d1\u984d\u3002\u521d\u671f\u5024\u306ftotal\u3068\u540c\u5024\u3067\u3001PointProcessor\u304c\u30dd\u30a4\u30f3\u30c8\u5024\u5f15\u304d\u306eOrderItem\uff08type=POINT_DISCOUNT\u3001\u4e0d\u8ab2\u7a0e\uff09\u3092\u8ffd\u52a0\u5f8c\u306bPurchaseFlow.calculateTotal()\u3067\u518d\u8a08\u7b97\u3055\u308c\u308b\u3002\u8a08\u7b97\u5f0f: total - (\u5229\u7528\u30dd\u30a4\u30f3\u30c8 x pointConversionRate) Fake\u89b3\u5bdf\u6570\u5024 12700\u301c12700; \u89b3\u5bdf\u5024 '12700'\u3002","example":12700,"minimum":0,"maximum":999999999},"total":{"type":["integer","null"],"title":"\u53d7\u6ce8\u5408\u8a08","description":"\u53d7\u6ce8\u5408\u8a08\u91d1\u984d\u3002\u8a08\u7b97\u5f0f: subtotal(\u5546\u54c1\u7a0e\u8fbc\u5408\u8a08) + deliveryFeeTotal(\u9001\u6599) + charge(\u624b\u6570\u6599) - discount(\u5024\u5f15\u304d)\u3002\u30ab\u30fc\u30c8\u306etotalPrice\u3068\u306f\u5225\u30d7\u30ed\u30d1\u30c6\u30a3 Fake\u89b3\u5bdf\u6570\u5024 12700\u301c12700; \u89b3\u5bdf\u5024 '12700'\u3002","example":12700,"minimum":0,"maximum":999999999},"itemCount":{"type":["integer","null"],"minimum":0,"maximum":10000,"title":"\u660e\u7d30\u4ef6\u6570","description":"/admin/index \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u3067\u8fd4\u3059\u660e\u7d30\u4ef6\u6570\u3002\u4e00\u89a7\u30fb\u96c6\u8a08\u30fb\u51e6\u7406\u7d50\u679c\u306e\u898f\u6a21\u3092\u8868\u3059\u975e\u8ca0\u6574\u6570\u3002","example":1}},"additionalProperties":false,"$comment":"\u914d\u5217\u8981\u7d20\u306fFake/Resource\u3067\u89b3\u5bdf\u3055\u308c\u305f\u65e2\u77e5property\u306b\u56fa\u5b9a\u3059\u308b\u3002\u65b0\u3057\u3044\u5217\u304c\u5fc5\u8981\u306b\u306a\u3063\u305f\u5834\u5408\u306fSemantic-Ex\u89b3\u5bdf\u306b\u8ffd\u52a0\u3057\u3066schema\u3092\u66f4\u65b0\u3059\u308b\u3002"},"minItems":0} |  |
| salesThisMonth | int|null | 当月売上 - /admin/index のレスポンスで返す当月売上。一覧、集計、CSV処理結果の規模を表す非負の数値。 | Required | {"minimum":0,"maximum":999999999} | 1200 |
| orderStatuses | array|null | 注文ステータス一覧 - /admin/index のレスポンスで扱う注文ステータス一覧。配列要素はALPS意味とFake観察に基づき、固定できない動的列は例外理由を台帳化する。 | Required | {"items":{"type":"object","title":"\u6ce8\u6587\u30b9\u30c6\u30fc\u30bf\u30b9","description":"/admin/index \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u6ce8\u6587\u30b9\u30c6\u30fc\u30bf\u30b9\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `orderStatuses` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002","properties":{"name":{"type":["string","null"],"minLength":0,"maxLength":32,"title":"\u51e6\u7406\u8868\u793a\u540d","description":"Fake\u89b3\u5bdf\u6587\u5b57\u9577 1\u301c7; \u89b3\u5bdf\u5024 '\u30c6\u30b9\u30c8\u7ba1\u7406\u8005', '\u526f\u7ba1\u7406\u8005', '\u5e97\u8217\u30aa\u30fc\u30ca\u30fc', '\u524a\u9664\u6e08\u307f\u7ba1\u7406\u8005', 'Red', 'Blue', 'S', 'Color'\u3002","example":"\u30c6\u30b9\u30c8\u7ba1\u7406\u8005"},"color":{"type":["string","null"],"minLength":0,"maxLength":255,"title":"\u8868\u793a\u9805\u76ee","description":"/admin/index \u306e\u753b\u9762\u8868\u793a\u306b\u4f7f\u3046\u8868\u793a\u9805\u76ee\u3002\u696d\u52d9\u30a8\u30f3\u30c6\u30a3\u30c6\u30a3\u305d\u306e\u3082\u306e\u3067\u306f\u306a\u304f\u30c6\u30f3\u30d7\u30ec\u30fc\u30c8/\u4e00\u89a7\u8868\u793a\u306e\u88dc\u52a9\u5024\u3002"},"count":{"type":["integer","null"],"minimum":0,"maximum":2147483647,"title":"\u4ef6\u6570","description":"/admin/index \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u3067\u8fd4\u3059\u4ef6\u6570\u3002\u4e00\u89a7\u30fb\u96c6\u8a08\u30fb\u51e6\u7406\u7d50\u679c\u306e\u898f\u6a21\u3092\u8868\u3059\u975e\u8ca0\u6574\u6570\u3002"}},"$comment":"\u914d\u5217\u8981\u7d20\u306f\u30de\u30b9\u30bf/CSV/option map\u306e\u52d5\u7684\u884c\u3067\u3042\u308a\u3001\u5217\u96c6\u5408\u304c\u5bfe\u8c61\u7a2e\u5225\u306b\u3088\u308a\u5909\u308f\u308b\u305f\u3081\u56fa\u5b9ashape\u5316\u3057\u306a\u3044\u3002\u65e2\u77e5property\u306f\u5951\u7d04\u3057\u3001\u8ffd\u52a0\u5217\u306f\u8a72\u5f53\u30b5\u30fc\u30d3\u30b9\u5883\u754c\u3067\u6271\u3046\u3002"},"minItems":0} |  |
| countCustomers | int|null | 会員数 - /admin/index のレスポンスで返す会員数。一覧、集計、CSV処理結果の規模を表す非負の数値。 | Required | {"minimum":0,"maximum":2147483647} |  |
| countProducts | int|null | 商品数 - /admin/index のレスポンスで返す商品数。一覧、集計、CSV処理結果の規模を表す非負の数値。 | Required | {"minimum":0,"maximum":2147483647} |  |
| salesYesterday | int|null | 昨日売上 - /admin/index のレスポンスで返す昨日売上。一覧、集計、CSV処理結果の規模を表す非負の数値。 | Required | {"minimum":0,"maximum":999999999} | 1200 |

#### Links

| Relation | URL |
|----------|-----|
| goMemberList | [<code>page://self/admin/member-list</code>](/admin/member-list.md) |
| goContentCache | [<code>page://self/admin/content/cache</code>](/admin/content/cache.md) |
| goOrderStatusList | [<code>page://self/admin/order-status</code>](/admin/order-status.md) |
| goChangePassword | [<code>page://self/admin/change-password</code>](/admin/change-password.md) |
| doAdminLogout | [<code>page://self/admin/logout</code>](/admin/logout.md) |
| goAdminLogout | [<code>page://self/admin/login</code>](/admin/login.md) |