<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /shopping/shipping
EC-CUBE goShoppingShipping — お届け先選択画面 (Wave 3H pure renderer).

Pure form-info endpoint: no Be Framework, no domain logic, no Reasons.
Maps to `page://self/shopping/shipping`. The submit target is
doSelectShippingAddress.

Production EC-CUBE populates the body with the authenticated
customer's registered shipping address list. Wave 3H exposes the
shape only; the data lookup (customer's address book under the
active pre-order) is left as TODO until a dedicated aggregation
lands — the renderer is intentionally anonymous-permissive (matches
other Shopping/* renderers under the Wave 3H scope).




## GET
ALPS `goShoppingShipping` に対応する GET 操作。

**ALPS**: `goShoppingShipping` - お届け先を選択する画面を見る



### Request

_No parameters required_

### Response

[Object: GET /shopping/shipping response](../schemas/get-shopping-shipping.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| fields | array|null | 静的表示フィールド - /shopping/shipping でテンプレートへ渡す表示用フィールド集合。フォーム入力値ではなく画面文脈データ。 | Optional | {"items":{"type":"string","title":"\u8868\u793a\u30d5\u30a3\u30fc\u30eb\u30c9","minLength":0,"maxLength":255,"description":"/shopping/shipping \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u6ce8\u6587\u884c\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `fields` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002"}} |  |
| submitTo | object|null | フォーム送信先リンク - /shopping/shipping のフォーム送信に使う送信先リンク。HTTPメソッドと遷移先をまとめ、unsafe操作の入口を明示する。 | Optional | {"properties":{"href":{"title":"\u30ea\u30f3\u30afURI\u53c2\u7167\uff08URI\u53c2\u7167\uff09","description":"\u30da\u30fc\u30b8\u306eURL\u30d1\u30b9\uff08Symfony\u30eb\u30fc\u30c8\u540d\u3002\u4f8b: homepage, product_list\uff09","type":"string","format":"uri-reference","minLength":1,"maxLength":2048,"example":"/products"},"method":{"type":["string","null"],"enum":["get","post","put","patch","delete","GET","POST","PUT","PATCH","DELETE"],"title":"HTTP\u30e1\u30bd\u30c3\u30c9","description":"/shopping/shipping \u306e\u30ea\u30f3\u30af\u307e\u305f\u306f\u30d5\u30a9\u30fc\u30e0\u9001\u4fe1\u3067\u4f7f\u3046HTTP\u30e1\u30bd\u30c3\u30c9\u3002GET/POST\u7b49\u306e\u9077\u79fb\u65b9\u6cd5\u3092\u8868\u3059\u3002"}},"additionalProperties":false,"required":["href","method"]} |  |
| staticContent | string|null | 静的コンテンツ - /shopping/shipping で表示する規約・ヘルプ・エラー等の静的ページ本文とセクション情報。 | Optional | {"minLength":0,"maxLength":255} |  |
| transitionId | string | ALPS遷移ID - このレスポンス/操作が対応するALPS遷移ID。クライアントの状態遷移追跡に使う。 | Required | {"minLength":2,"maxLength":96,"pattern":"^(go|do)[A-Z][A-Za-z0-9]*$"} | doAddCartItem |
| addresses | array|null | 住所一覧 - /shopping/shipping のレスポンスで扱う住所一覧。配列要素はALPS意味とFake観察に基づき、固定できない動的列は例外理由を台帳化する。 | Required | {"items":{"type":"object","title":"\u4f4f\u6240","description":"/shopping/shipping \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u4f4f\u6240\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `addresses` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002","properties":{"addressId":{"type":["string","null"],"title":"\u914d\u9001\u5148\u4f4f\u6240ID","description":"dtb_customer_address.id \u306e\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217\u30cf\u30f3\u30c9\u30eb\u3002BeMart \u306e AddressEntity \u5c64\u306f\u6570\u5024\u3067\u306f\u306a\u304f\u6587\u5b57\u5217\u3068\u3057\u3066\u4fdd\u6301\u3059\u308b\u3002Fake \u5b9f\u88c5\u306f 32\u6841hex \u3092\u751f\u6210\u3057\u3001SQL \u5b9f\u88c5\u306f dtb_customer_address.id (int unsigned AUTO_INCREMENT) \u3092\u6587\u5b57\u5217\u5316\u3057\u3066\u4f7f\u7528\uff08\u540c\u30a4\u30f3\u30bf\u30fc\u30d5\u30a7\u30a4\u30b9\u30fb\u7570 ID \u5f62\u72b6\uff09\u3002\u6240\u6709\u8005\u306f customerId\u3001AUTHZ \u691c\u67fb\u306f CustomerAddressUpdated / CustomerAddressDeleted \u3067 getById \u2192 customerId \u4e00\u81f4\u78ba\u8a8d\u306e\u9806\u3067\u5b9f\u65bd Fake\u89b3\u5bdf\u6587\u5b57\u9577 32\u301c32; \u89b3\u5bdf\u5024 'addr00000000000000000000000000a1'\u3002","example":"addr00000000000000000000000000a1","minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"},"shippingAddressId":{"type":["integer","null"],"minimum":0,"maximum":2147483647,"title":"\u914d\u9001\u5148ID","description":"/shopping/shipping \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u3067\u5bfe\u8c61\u3092\u8b58\u5225\u3059\u308b\u914d\u9001\u5148ID\u3002DB\u63a1\u756aID\u3001Fake\u6587\u5b57\u5217ID\u3001\u4e92\u63db\u5883\u754cID\u306e\u3069\u308c\u306b\u8a72\u5f53\u3059\u308b\u304b\u3092schema\u306e\u578b\u3068\u30b3\u30e1\u30f3\u30c8\u3067\u5206\u3051\u308b\u3002","$comment":"EC-CUBE\u5074\u306e\u63a1\u756aID\u3068\u3057\u3066\u6271\u3046\u3002"},"name01":{"type":["string","null"],"minLength":0,"maxLength":80,"title":"\u59d3","description":"\u9867\u5ba2\u30fb\u53d7\u6ce8\u30fb\u914d\u9001\u5148\u30fb\u304a\u554f\u3044\u5408\u308f\u305b\u3067\u5171\u901a\u4f7f\u7528\u3055\u308c\u308b\u59d3 Fake\u89b3\u5bdf\u6587\u5b57\u9577 2\u301c2; \u89b3\u5bdf\u5024 '\u9234\u6728', '\u5c71\u7530', '\u4f50\u85e4', '\u9ad8\u6a4b', '\u9000\u4f1a'\u3002","example":"\u9234\u6728"},"name02":{"type":["string","null"],"minLength":0,"maxLength":80,"title":"\u540d","description":"\u9867\u5ba2\u30fb\u53d7\u6ce8\u30fb\u914d\u9001\u5148\u30fb\u304a\u554f\u3044\u5408\u308f\u305b\u3067\u5171\u901a\u4f7f\u7528\u3055\u308c\u308b\u540d Fake\u89b3\u5bdf\u6587\u5b57\u9577 1\u301c3; \u89b3\u5bdf\u5024 '\u30a2\u30ea\u30b9', '\u592a\u90ce', '\u6b21\u90ce', '\u82b1\u5b50', '\u4e09\u90ce', '\u6e08'\u3002","example":"\u30a2\u30ea\u30b9"},"postalCode":{"title":"\u90f5\u4fbf\u756a\u53f7","description":"\u65e5\u672c\u306e\u90f5\u4fbf\u756a\u53f7\u3002\u30cf\u30a4\u30d5\u30f3\u306a\u30577\u6841\u307e\u305f\u306f\u30cf\u30a4\u30d5\u30f3\u4ed8\u304d8\u6841 \u65e5\u672c\u306e\u90f5\u4fbf\u756a\u53f7\u3002\u5165\u529b\u30d5\u30a9\u30fc\u30e0\u3067\u306f\u30cf\u30a4\u30d5\u30f3\u6709\u7121\u3092\u3069\u3061\u3089\u3082\u53d7\u3051\u5165\u308c\u308b\u3002 Fake\u89b3\u5bdf\u6587\u5b57\u9577 7\u301c8; \u89b3\u5bdf\u5024 '1500001', '1000005', '5300001', '530-0001'; null 18/33\u3002","type":["string","null"],"pattern":"^\\d{3}-?\\d{4}$","example":"1500001"},"pref":{"title":"\u90fd\u9053\u5e9c\u770c","description":"\u65e5\u672c\u306e\u90fd\u9053\u5e9c\u770c\uff081=\u5317\u6d77\u9053\u301c47=\u6c96\u7e04\u770c\uff09\u3002\u4f4f\u6240\u306e\u6700\u4e0a\u4f4d\u533a\u5206\u3068\u3057\u3066\u9867\u5ba2\u30fb\u53d7\u6ce8\u30fb\u914d\u9001\u5148\u3067\u4f7f\u7528\u3002\u914d\u9001\u6599\u306e\u5730\u57df\u533a\u5206\uff08DeliveryFee\uff09\u3084\u7a0e\u7387\u306e\u5730\u57df\u8a2d\u5b9a\uff08TaxRule\uff09\u306b\u3082\u4f7f\u7528 \u90fd\u9053\u5e9c\u770cID\u3002\u4f4f\u6240\u30d5\u30a9\u30fc\u30e0\u306e\u672a\u9078\u629e\u72b6\u614b\u3067\u306f0\u3001\u78ba\u5b9a\u4f4f\u6240\u3067\u306f1\u301c47\u3092\u4f7f\u3046\u3002 Fake\u89b3\u5bdf\u6570\u5024 13\u301c27; \u89b3\u5bdf\u5024 '13', '27'; null 3/9\u3002","type":["integer","null"],"minimum":0,"maximum":47,"example":13},"addr01":{"type":["string","null"],"minLength":0,"maxLength":32,"title":"\u5e02\u533a\u753a\u6751","description":"\u90fd\u9053\u5e9c\u770c\u3088\u308a\u4e0b\u4f4d\u306e\u5e02\u533a\u753a\u6751\u540d Fake\u89b3\u5bdf\u6587\u5b57\u9577 3\u301c7; \u89b3\u5bdf\u5024 '\u6e0b\u8c37\u533a', '\u5343\u4ee3\u7530\u533a', '\u5927\u962a\u5e02\u5317\u533a', '\u5927\u962a\u5e02\u5317\u533a\u6885\u7530'; null 18/33\u3002","example":"\u6e0b\u8c37\u533a"},"addr02":{"type":["string","null"],"minLength":0,"maxLength":32,"title":"\u756a\u5730\u30fb\u5efa\u7269\u540d","description":"\u756a\u5730\u30fb\u30d3\u30eb\u540d\u30fb\u90e8\u5c4b\u756a\u53f7\u7b49\u306e\u8a73\u7d30\u4f4f\u6240 Fake\u89b3\u5bdf\u6587\u5b57\u9577 5\u301c8; \u89b3\u5bdf\u5024 '\u795e\u5bae\u524d1-1-1', '\u4e38\u306e\u51852-2-2', '\u6885\u75301-1-1', '1-2-3'; null 18/33\u3002","example":"\u795e\u5bae\u524d1-1-1"},"phoneNumber":{"title":"\u96fb\u8a71\u756a\u53f7","description":"\u65e5\u672c\u306e\u96fb\u8a71\u756a\u53f7\u5f62\u5f0f\uff08\u30cf\u30a4\u30d5\u30f3\u533a\u5207\u308a\uff09 \u65e5\u672c\u306e\u96fb\u8a71\u756a\u53f7\u3002Fake corpus\u306f\u30cf\u30a4\u30d5\u30f3\u306a\u3057\u4e2d\u5fc3\u3060\u304c\u3001\u5165\u529b\u3067\u306f\u30cf\u30a4\u30d5\u30f3\u4ed8\u304d\u3082\u8a31\u5bb9\u3059\u308b\u3002 Fake\u89b3\u5bdf\u6587\u5b57\u9577 10\u301c10; \u89b3\u5bdf\u5024 '0312345678', '0901234567', '0612345678'; null 18/33\u3002","type":["string","null"],"pattern":"^0\\d{1,4}-?\\d{1,4}-?\\d{3,4}$","minLength":10,"maxLength":13,"example":"0312345678"}},"additionalProperties":false,"$comment":"\u914d\u5217\u8981\u7d20\u306fFake/Resource\u3067\u89b3\u5bdf\u3055\u308c\u305f\u65e2\u77e5property\u306b\u56fa\u5b9a\u3059\u308b\u3002\u65b0\u3057\u3044\u5217\u304c\u5fc5\u8981\u306b\u306a\u3063\u305f\u5834\u5408\u306fSemantic-Ex\u89b3\u5bdf\u306b\u8ffd\u52a0\u3057\u3066schema\u3092\u66f4\u65b0\u3059\u308b\u3002"},"minItems":0} |  |
| csrfToken | string|null | 処理識別子 - フォーム送信の偽造を防ぐために送信元画面で発行されるトークン。Fake環境では deterministic な値を使う。 | Optional | {"minLength":8,"maxLength":160,"pattern":"^[A-Za-z0-9_.:-]+$"} | fake-csrf-token-bemart-2026 |

#### Links

| Relation | URL |
|----------|-----|
| doSelectShippingAddress | [<code>page://self/shopping/shipping</code>](/shopping/shipping.md) |
| goShoppingShippingEdit | [<code>page://self/shopping/shipping-edit</code>](/shopping/shipping-edit.md) |
| goShoppingShippingMultiple | [<code>page://self/shopping/shipping-multiple</code>](/shopping/shipping-multiple.md) |
## POST
EC-CUBE doSelectShippingAddress — accept the selected address-book row.

This closes the former ActionRedirect gap for the customer checkout
route. The current shopping renderer does not yet hydrate the address
radio list, so the resource records the selected id in the response
surface and returns to the shopping page. The full pre-order shipping
persistence is intentionally left to the existing checkout enrichment
backlog; this method makes the route executable without a placeholder.

**ALPS**: `doSelectShippingAddress` - お届け先を選択する



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| shippingAddressId | string | 配送先ID（入力） - /shopping/shipping のレスポンスで対象を識別する配送先ID。DB採番ID、Fake文字列ID、互換境界IDのどれに該当するかをschemaの型とコメントで分ける。 |  | Optional | {"$comment":"\u914d\u9001\u5148ID\uff08\u5165\u529b\uff09\u306f\u696d\u52d9\u4e0aID\u3060\u304c\u3001HTTP\u30d5\u30a9\u30fc\u30e0\u3067\u306f\u6587\u5b57\u5217\u3068\u3057\u3066\u5c4a\u304f\u3002Resource/Semantic\u5c64\u306e\u691c\u8a3c\u3092\u901a\u3059\u305f\u3081transport schema\u3067\u306fstring|integer\u3092\u8a31\u5bb9\u3059\u308b\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation.","minLength":0,"maxLength":128} |  |


### Response

[Object: POST /shopping/shipping response](../schemas/post-shopping-shipping.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| message | string|null | 注文メッセージ - /shopping/shipping のレスポンスに含まれる処理結果メッセージ。注文時お問い合わせ欄ではなく、画面遷移や完了表示のための通知文。 | Optional | {"minLength":0,"maxLength":32} | 配送は平日希望です。 |
| transitionId | string | ALPS遷移ID - このレスポンス/操作が対応するALPS遷移ID。クライアントの状態遷移追跡に使う。 | Required | {"minLength":2,"maxLength":96,"pattern":"^(go|do)[A-Z][A-Za-z0-9]*$"} | doAddCartItem |
| shippingAddressId | int|null | 配送先ID - /shopping/shipping のレスポンスで対象を識別する配送先ID。DB採番ID、Fake文字列ID、互換境界IDのどれに該当するかをschemaの型とコメントで分ける。 | Required | {"minimum":0,"maximum":2147483647,"$comment":"EC-CUBE\u5074\u306e\u63a1\u756aID\u3068\u3057\u3066\u6271\u3046\u3002"} |  |

#### Links

| Relation | URL |
|----------|-----|
| goShopping | [<code>page://self/shopping</code>](/shopping.md) |