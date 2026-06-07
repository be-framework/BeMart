<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /product
EC-CUBE goProduct —商品詳細ページ。

Resource is the HTTP entry point: it builds a Be Input, hands it to
Becoming, and projects the resulting Final into the response body.
All validation and DB access live in the Be domain layer.

Phase 3 — HTML page. The product detail page carries the add-to-cart
action, which EC-CUBE renders as a FORM (`AddCartType` — quantity +,
for class products, the product-class selects). The resource builds
an {@see \AddCartForm} (Ray.WebFormModule AbstractForm), seeds its
hidden `product_id` with the product code, and exposes it as
`body['form']` so the HTML port can render the real quantity
`<input>` via `{{ form.input('quantity') }}`. The form is a
field-definition + renderer only — VALIDATION AUTHORITY STAYS WITH the
Be Framework Becoming chain (the Cart add-item Input). JSON contexts
(`app`, `prod`, `test`) ignore `body['form']`; the JSON-context tests
assert key-wise on `body` and are unaffected.

FormFactory is self-sufficient (no Ray.Di bindings needed), so the
resource builds the form in every context cheaply; only the `html`
context's TwigRenderer actually renders it.




## GET
Phase B Slice 9: `$productCode` is user input (URI / query param);
declared explicitly so Psalm taint analysis can trace it through
Becoming into any downstream sink. The Be Semantic\ProductCode
constructor format-validates but does not escape — sinks downstream
still need to defend (e.g. bound parameters for SQL).

**ALPS**: `goProduct`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| productCode | string | 商品コード（入力） - SKU/品番。在庫管理や受注明細での識別に使用 商品を識別するSKU。Fake corpusではASCII英数・ハイフン中心で、受注明細/カート明細の結合キーになる。 Fake観察文字長 10〜26。 |  | Required | {"minLength":0,"maxLength":64,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | sample-001 |


### Response

[Object: GET /product response](../schemas/get-product.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| form | object|array|null | 入力フォーム - Aura/WebForm由来のフォームオブジェクト。フレームワーク内部構造のためschemaでは存在と型のみを契約する。 | Optional | {"$comment":"Aura/WebForm\u7531\u6765\u306e\u4e0d\u900f\u660e\u30d5\u30a9\u30fc\u30e0\u8868\u73fe\u3002Resource\u5883\u754c\u3067\u306f\u30d5\u30a9\u30fc\u30e0\u306e\u5b58\u5728\u3068\u30b3\u30f3\u30c6\u30ad\u30b9\u30c8\u3060\u3051\u3092\u5951\u7d04\u3057\u3001\u5185\u90e8\u69cb\u9020\u306f\u30d5\u30ec\u30fc\u30e0\u30ef\u30fc\u30af\u5883\u754c\u306b\u59d4\u306d\u308b\u305f\u3081\u8ffd\u52a0\u30ad\u30fc\u5236\u7d04\u3092\u7f6e\u304b\u306a\u3044\u3002"} |  |
| stock | int|null | 在庫数 - 物理在庫数。stockUnlimited=trueの場合は無視される。注文確定時に引き当てが行われる Fake観察数値 0〜100; 観察値 '0', '10', '20', '50', '5', '7', '100', '3'; null 9/73。 | Optional | {"minimum":0,"maximum":2147483647} | 0 |
| productName | string|null | 商品名 - 商品の表示名 Fake観察文字長 6〜17。 | Required | {"minLength":0,"maxLength":128} | サンプル商品 A |
| description | string|null | 詳細説明文 - 商品詳細ページに表示する説明文 Fake観察文字長 12〜32; 観察値 'Stock-unlimited fixture', 'Wave 8 admin grid: visible row', 'Wave 8 admin grid: hidden row', 'Wave 8 admin grid: withdrawn row', '管理画面から名称変更した、彩り豊かなジェラートセットです。', '管理画面から作成した商品'; null 1/7。 | Optional | {"minLength":0,"maxLength":128} | Stock-unlimited fixture |
| classNames | array|null | 規格名一覧 - Fake観察数値 0〜1。 | Optional | {"items":{"type":"string","title":"\u898f\u683c\u540d","minLength":0,"maxLength":128,"description":"/product \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u898f\u683c\u540d\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `classNames` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002"},"minItems":0} |  |
| price02 | int|null | 販売価格 - 実際の販売価格（税抜）。税計算・小計計算のベース Fake観察数値 800〜28000。 | Required | {"minimum":0,"maximum":999999999} | 3500 |
| mainImage | string|null | メイン画像URI - /product の画面表示に使うメイン画像URI。業務エンティティそのものではなくテンプレート/一覧表示の補助値。 | Optional | {"format":"uri-reference","minLength":1,"maxLength":2048} | /products |
| tagNames | array|null | タグ名一覧 - Fake観察数値 0〜2。 | Optional | {"items":{"type":"string","title":"\u30bf\u30b0\u540d","minLength":0,"maxLength":128,"description":"/product \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u5546\u54c1\u884c\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `tagNames` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002"},"minItems":0} |  |
| categoryNames | array|null | カテゴリ名一覧 - Fake観察数値 0〜4。 | Optional | {"items":{"type":"string","title":"\u30ab\u30c6\u30b4\u30ea\u540d","minLength":0,"maxLength":128,"description":"/product \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u5546\u54c1\u884c\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `categoryNames` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002"},"minItems":0} |  |
| productCode | string | 商品コード - SKU/品番。在庫管理や受注明細での識別に使用 商品を識別するSKU。Fake corpusではASCII英数・ハイフン中心で、受注明細/カート明細の結合キーになる。 Fake観察文字長 10〜26。 | Required | {"minLength":0,"maxLength":64} | sample-001 |
| stockFind | boolean|null | 在庫検索フラグ - /product の処理文脈から派生した在庫検索フラグ。ALPS基礎語だけでは単位や用途が不足するため、このResource上の意味を明示する。 | Required | {"minLength":0,"maxLength":255} |  |
| csrfToken | string|null | 処理識別子 - フォーム送信の偽造を防ぐために送信元画面で発行されるトークン。Fake環境では deterministic な値を使う。 | Optional | {"minLength":8,"maxLength":160,"pattern":"^[A-Za-z0-9_.:-]+$"} | fake-csrf-token-bemart-2026 |

#### Links

| Relation | URL |
|----------|-----|
| goProductList | [<code>page://self/products</code>](/products.md) |
| doAddCartItem | [<code>page://self/cart/item</code>](/cart/item.md) |
| doAddFavorite | [<code>page://self/mypage/favorite</code>](/mypage/favorite.md) |
| doRemoveFavorite | [<code>page://self/mypage/favorite</code>](/mypage/favorite.md) |