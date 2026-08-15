<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/product
EC-CUBE admin product surface — combines goProduct (admin variant),
doCreateProduct, doUpdateProduct, doDeleteProduct in one
ResourceObject keyed at `page://self/admin/product`.

Method routing:
  - onGet    — goProduct (admin variant) → 200 / 403 / 404
  - onPost   — doCreateProduct           → 201 / 400 / 403 / 409
  - onPut    — doUpdateProduct           → 200 / 400 / 403 / 404
  - onDelete — doDeleteProduct           → 200 (incl. alreadyDeleted) / 400 / 403 / 404

The customer-facing Product.php (Pilot 1) lives at
`page://self/product` — a sibling resource for the consumer path
(shallow body, no AUTHZ). This admin resource surfaces the full
ProductEntity including the admin-only columns (note, searchWord,
productStatus).

CSRF: enforced on every state-changing method (POST/PUT/DELETE).
The onGet path is read-only and skips CSRF (same convention as
AdminCustomer onGet).




## GET
goProduct (admin variant) — fetch full product detail.

**ALPS**: `goProduct` - 商品詳細を見る



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| productCode | string | 商品コード（入力） - SKU/品番。在庫管理や受注明細での識別に使用 商品を識別するSKU。Fake corpusではASCII英数・ハイフン中心で、受注明細/カート明細の結合キーになる。 Fake観察文字長 10〜26。 |  | Required | {"minLength":0,"maxLength":64,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | sample-001 |


### Response

[Object: GET /admin/product response](../schemas/get-admin-product.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| stock | int|null | 在庫数 - 物理在庫数。stockUnlimited=trueの場合は無視される。注文確定時に引き当てが行われる Fake観察数値 0〜100; 観察値 '0', '10', '20', '50', '5', '7', '100', '3'; null 9/73。 | Optional | {"minimum":0,"maximum":2147483647} | 0 |
| productName | string|null | 商品名 - 商品の表示名 Fake観察文字長 6〜17。 | Required | {"minLength":0,"maxLength":128} | サンプル商品 A |
| description | string|null | 詳細説明文 - 商品詳細ページに表示する説明文 Fake観察文字長 12〜32; 観察値 'Stock-unlimited fixture', 'Wave 8 admin grid: visible row', 'Wave 8 admin grid: hidden row', 'Wave 8 admin grid: withdrawn row', '管理画面から名称変更した、彩り豊かなジェラートセットです。', '管理画面から作成した商品'; null 1/7。 | Optional | {"minLength":0,"maxLength":128} | Stock-unlimited fixture |
| classNames | array|null | 規格名一覧 - Fake観察数値 0〜1。 | Optional | {"items":{"type":"string","title":"\u898f\u683c\u540d","minLength":0,"maxLength":128,"description":"/admin/product \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u898f\u683c\u540d\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `classNames` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002"},"minItems":0} |  |
| price02 | int|null | 販売価格 - 実際の販売価格（税抜）。税計算・小計計算のベース Fake観察数値 800〜28000。 | Required | {"minimum":0,"maximum":999999999} | 3500 |
| mainImage | string|null | メイン画像URI - /admin/product の画面表示に使うメイン画像URI。業務エンティティそのものではなくテンプレート/一覧表示の補助値。 | Optional | {"format":"uri-reference","minLength":1,"maxLength":2048} | /products |
| tagNames | array|null | タグ名一覧 - Fake観察数値 0〜2。 | Optional | {"items":{"type":"string","title":"\u30bf\u30b0\u540d","minLength":0,"maxLength":128,"description":"/admin/product \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u5546\u54c1\u884c\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `tagNames` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002"},"minItems":0} |  |
| categoryNames | array|null | カテゴリ名一覧 - Fake観察数値 0〜4。 | Optional | {"items":{"type":"string","title":"\u30ab\u30c6\u30b4\u30ea\u540d","minLength":0,"maxLength":128,"description":"/admin/product \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u5546\u54c1\u884c\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `categoryNames` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002"},"minItems":0} |  |
| productCode | string | 商品コード - SKU/品番。在庫管理や受注明細での識別に使用 商品を識別するSKU。Fake corpusではASCII英数・ハイフン中心で、受注明細/カート明細の結合キーになる。 Fake観察文字長 10〜26。 | Required | {"minLength":0,"maxLength":64} | sample-001 |
| note | string|null | 商品備考 - 管理者のみが参照する内部メモ。フロントには表示されない Fake観察文字長 11〜25; 観察値 'internal note A', 'internal note C', 'internal note B', '管理画面名称変更 smoke 2026-05-23', 'Codex smoke'; null 10/42。 | Optional | {"minLength":0,"maxLength":1000} | internal note A |
| productStatusOptions | object | 商品ステータス - 1=公開（フロント表示）, 2=非公開（フロント非表示）, 3=廃止（論理削除、管理画面でもデフォルト非表示） | Required | {"$comment":"\u30d5\u30ec\u30fc\u30e0\u30ef\u30fc\u30af\u7531\u6765\u307e\u305f\u306f\u52d5\u7684map\u306e\u305f\u3081\u3001JSON\u5883\u754c\u3067\u306fobject\u3067\u3042\u308b\u3053\u3068\u3068\u610f\u5473\u3092\u5951\u7d04\u3057\u3001\u5185\u90e8\u30ad\u30fc\u306f\u5225\u5883\u754c\u3067\u6271\u3046\u3002\u8ffd\u52a0\u30ad\u30fc\u306f\u4e0d\u900f\u660e\u69cb\u9020\u3068\u3057\u3066\u8a31\u5bb9\u3059\u308b\u3002"} |  |
| productStatus | int|null | 商品ステータス - 1=公開（フロント表示）, 2=非公開（フロント非表示）, 3=廃止（論理削除、管理画面でもデフォルト非表示） Fake観察数値 1〜3; 観察値 '1', '2', '3'。 | Required | {"enum":[1,2,3]} | 1 |
| csrfToken | string|null | 処理識別子 - フォーム送信の偽造を防ぐために送信元画面で発行されるトークン。Fake環境では deterministic な値を使う。 | Optional | {"minLength":8,"maxLength":160,"pattern":"^[A-Za-z0-9_.:-]+$"} | fake-csrf-token-bemart-2026 |
| searchWord | string|null | 検索ワード - フロント検索でヒットさせるためのキーワード。画面には表示されない検索補助データ Fake観察文字長 9〜15; 観察値 '管理 active', '管理 withdrawn', '管理 hidden', 'ジェラート ギフト 彩 api', 'ui create'; null 10/42。 | Optional | {"minLength":0,"maxLength":1000} | 管理 active |

#### Links

| Relation | URL |
|----------|-----|
| goProductList | [<code>page://self/admin/product-list</code>](/admin/product-list.md) |
| doUpdateProduct | [<code>page://self/admin/product</code>](/admin/product.md) |
| doDeleteProduct | [<code>page://self/admin/product</code>](/admin/product.md) |
| doCopyProduct | [<code>page://self/admin/product-copy</code>](/admin/product-copy.md) |
## POST
doCreateProduct — create a new product.

**ALPS**: `doCreateProduct` - 商品を作成する



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| productCode | string | 商品コード（入力） - SKU/品番。在庫管理や受注明細での識別に使用 商品を識別するSKU。Fake corpusではASCII英数・ハイフン中心で、受注明細/カート明細の結合キーになる。 Fake観察文字長 10〜26。 |  | Required | {"minLength":0,"maxLength":64,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | sample-001 |
| productName | string | 商品名（入力） - 商品の表示名 Fake観察文字長 6〜17。 |  | Required | {"minLength":0,"maxLength":128,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | サンプル商品 A |
| price02 | int | 販売価格（入力） - 実際の販売価格（税抜）。税計算・小計計算のベース Fake観察数値 800〜28000。 |  | Required | {"$comment":"\u8ca9\u58f2\u4fa1\u683c\uff08\u5165\u529b\uff09\u306f\u672c\u6765\u6570\u5024/\u5217\u6319\u306e\u696d\u52d9\u5024\u3060\u304c\u3001HTTP\u30d5\u30a9\u30fc\u30e0\u3067\u306f\u6587\u5b57\u5217\u3068\u3057\u3066\u5c4a\u304f\u3002Resource/Semantic\u5c64\u306e400\u5fdc\u7b54\u3092\u596a\u308f\u306a\u3044\u305f\u3081transport schema\u3067\u306f\u6587\u5b57\u5217\u5165\u529b\u3092\u8a31\u5bb9\u3059\u308b\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 3500 |
| stock | int | 在庫数（入力） - 物理在庫数。stockUnlimited=trueの場合は無視される。注文確定時に引き当てが行われる Fake観察数値 0〜100; 観察値 '0', '10', '20', '50', '5', '7', '100', '3'; null 9/73。 |  | Optional | {"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 0 |
| productStatus | int | 商品ステータス（入力） - 1=公開（フロント表示）, 2=非公開（フロント非表示）, 3=廃止（論理削除、管理画面でもデフォルト非表示） Fake観察数値 1〜3; 観察値 '1', '2', '3'。 |  | Optional | {"$comment":"\u5546\u54c1\u30b9\u30c6\u30fc\u30bf\u30b9\uff08\u5165\u529b\uff09\u306f\u672c\u6765\u6570\u5024/\u5217\u6319\u306e\u696d\u52d9\u5024\u3060\u304c\u3001HTTP\u30d5\u30a9\u30fc\u30e0\u3067\u306f\u6587\u5b57\u5217\u3068\u3057\u3066\u5c4a\u304f\u3002Resource/Semantic\u5c64\u306e400\u5fdc\u7b54\u3092\u596a\u308f\u306a\u3044\u305f\u3081transport schema\u3067\u306f\u6587\u5b57\u5217\u5165\u529b\u3092\u8a31\u5bb9\u3059\u308b\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 1 |
| description | string | 詳細説明文（入力） - 商品詳細ページに表示する説明文 Fake観察文字長 12〜32; 観察値 'Stock-unlimited fixture', 'Wave 8 admin grid: visible row', 'Wave 8 admin grid: hidden row', 'Wave 8 admin grid: withdrawn row', '管理画面から名称変更した、彩り豊かなジェラートセットです。', '管理画面から作成した商品'; null 1/7。 |  | Optional | {"minLength":0,"maxLength":128,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | Stock-unlimited fixture |
| searchWord | string | 検索ワード（入力） - フロント検索でヒットさせるためのキーワード。画面には表示されない検索補助データ Fake観察文字長 9〜15; 観察値 '管理 active', '管理 withdrawn', '管理 hidden', 'ジェラート ギフト 彩 api', 'ui create'; null 10/42。 |  | Optional | {"minLength":0,"maxLength":1000,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 管理 active |
| note | string | 商品備考（入力） - 管理者のみが参照する内部メモ。フロントには表示されない Fake観察文字長 11〜25; 観察値 'internal note A', 'internal note C', 'internal note B', '管理画面名称変更 smoke 2026-05-23', 'Codex smoke'; null 10/42。 |  | Optional | {"minLength":0,"maxLength":1000,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | internal note A |


### Response

[Object: POST /admin/product response](../schemas/post-admin-product.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| stock | int|null | 在庫数 - 物理在庫数。stockUnlimited=trueの場合は無視される。注文確定時に引き当てが行われる Fake観察数値 0〜100; 観察値 '0', '10', '20', '50', '5', '7', '100', '3'; null 9/73。 | Optional | {"minimum":0,"maximum":2147483647} | 0 |
| productName | string|null | 商品名 - 商品の表示名 Fake観察文字長 6〜17。 | Required | {"minLength":0,"maxLength":128} | サンプル商品 A |
| productCode | string | 商品コード - SKU/品番。在庫管理や受注明細での識別に使用 商品を識別するSKU。Fake corpusではASCII英数・ハイフン中心で、受注明細/カート明細の結合キーになる。 Fake観察文字長 10〜26。 | Required | {"minLength":0,"maxLength":64} | sample-001 |
| description | string|null | 詳細説明文 - 商品詳細ページに表示する説明文 Fake観察文字長 12〜32; 観察値 'Stock-unlimited fixture', 'Wave 8 admin grid: visible row', 'Wave 8 admin grid: hidden row', 'Wave 8 admin grid: withdrawn row', '管理画面から名称変更した、彩り豊かなジェラートセットです。', '管理画面から作成した商品'; null 1/7。 | Optional | {"minLength":0,"maxLength":128} | Stock-unlimited fixture |
| productStatus | int|null | 商品ステータス - 1=公開（フロント表示）, 2=非公開（フロント非表示）, 3=廃止（論理削除、管理画面でもデフォルト非表示） Fake観察数値 1〜3; 観察値 '1', '2', '3'。 | Required | {"enum":[1,2,3]} | 1 |
| price02 | int|null | 販売価格 - 実際の販売価格（税抜）。税計算・小計計算のベース Fake観察数値 800〜28000。 | Required | {"minimum":0,"maximum":999999999} | 3500 |

## PUT
doUpdateProduct — edit an existing product (partial overwrite).

**ALPS**: `doUpdateProduct` - 商品を更新する



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| productCode | string | 商品コード（入力） - SKU/品番。在庫管理や受注明細での識別に使用 商品を識別するSKU。Fake corpusではASCII英数・ハイフン中心で、受注明細/カート明細の結合キーになる。 Fake観察文字長 10〜26。 |  | Required | {"minLength":0,"maxLength":64,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | sample-001 |
| productName | string | 商品名（入力） - 商品の表示名 Fake観察文字長 6〜17。 |  | Optional | {"minLength":0,"maxLength":128,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | サンプル商品 A |
| price02 | int | 販売価格（入力） - 実際の販売価格（税抜）。税計算・小計計算のベース Fake観察数値 800〜28000。 |  | Optional | {"$comment":"\u8ca9\u58f2\u4fa1\u683c\uff08\u5165\u529b\uff09\u306f\u672c\u6765\u6570\u5024/\u5217\u6319\u306e\u696d\u52d9\u5024\u3060\u304c\u3001HTTP\u30d5\u30a9\u30fc\u30e0\u3067\u306f\u6587\u5b57\u5217\u3068\u3057\u3066\u5c4a\u304f\u3002Resource/Semantic\u5c64\u306e400\u5fdc\u7b54\u3092\u596a\u308f\u306a\u3044\u305f\u3081transport schema\u3067\u306f\u6587\u5b57\u5217\u5165\u529b\u3092\u8a31\u5bb9\u3059\u308b\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 3500 |
| stock | int | 在庫数（入力） - 物理在庫数。stockUnlimited=trueの場合は無視される。注文確定時に引き当てが行われる Fake観察数値 0〜100; 観察値 '0', '10', '20', '50', '5', '7', '100', '3'; null 9/73。 |  | Optional | {"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 0 |
| productStatus | int | 商品ステータス（入力） - 1=公開（フロント表示）, 2=非公開（フロント非表示）, 3=廃止（論理削除、管理画面でもデフォルト非表示） Fake観察数値 1〜3; 観察値 '1', '2', '3'。 |  | Optional | {"$comment":"\u5546\u54c1\u30b9\u30c6\u30fc\u30bf\u30b9\uff08\u5165\u529b\uff09\u306f\u672c\u6765\u6570\u5024/\u5217\u6319\u306e\u696d\u52d9\u5024\u3060\u304c\u3001HTTP\u30d5\u30a9\u30fc\u30e0\u3067\u306f\u6587\u5b57\u5217\u3068\u3057\u3066\u5c4a\u304f\u3002Resource/Semantic\u5c64\u306e400\u5fdc\u7b54\u3092\u596a\u308f\u306a\u3044\u305f\u3081transport schema\u3067\u306f\u6587\u5b57\u5217\u5165\u529b\u3092\u8a31\u5bb9\u3059\u308b\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 1 |
| description | string | 詳細説明文（入力） - 商品詳細ページに表示する説明文 Fake観察文字長 12〜32; 観察値 'Stock-unlimited fixture', 'Wave 8 admin grid: visible row', 'Wave 8 admin grid: hidden row', 'Wave 8 admin grid: withdrawn row', '管理画面から名称変更した、彩り豊かなジェラートセットです。', '管理画面から作成した商品'; null 1/7。 |  | Optional | {"minLength":0,"maxLength":128,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | Stock-unlimited fixture |
| searchWord | string | 検索ワード（入力） - フロント検索でヒットさせるためのキーワード。画面には表示されない検索補助データ Fake観察文字長 9〜15; 観察値 '管理 active', '管理 withdrawn', '管理 hidden', 'ジェラート ギフト 彩 api', 'ui create'; null 10/42。 |  | Optional | {"minLength":0,"maxLength":1000,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 管理 active |
| note | string | 商品備考（入力） - 管理者のみが参照する内部メモ。フロントには表示されない Fake観察文字長 11〜25; 観察値 'internal note A', 'internal note C', 'internal note B', '管理画面名称変更 smoke 2026-05-23', 'Codex smoke'; null 10/42。 |  | Optional | {"minLength":0,"maxLength":1000,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | internal note A |


### Response

[Object: PUT /admin/product response](../schemas/put-admin-product.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| stock | int|null | 在庫数 - 物理在庫数。stockUnlimited=trueの場合は無視される。注文確定時に引き当てが行われる Fake観察数値 0〜100; 観察値 '0', '10', '20', '50', '5', '7', '100', '3'; null 9/73。 | Optional | {"minimum":0,"maximum":2147483647} | 0 |
| productName | string|null | 商品名 - 商品の表示名 Fake観察文字長 6〜17。 | Required | {"minLength":0,"maxLength":128} | サンプル商品 A |
| productCode | string | 商品コード - SKU/品番。在庫管理や受注明細での識別に使用 商品を識別するSKU。Fake corpusではASCII英数・ハイフン中心で、受注明細/カート明細の結合キーになる。 Fake観察文字長 10〜26。 | Required | {"minLength":0,"maxLength":64} | sample-001 |
| description | string|null | 詳細説明文 - 商品詳細ページに表示する説明文 Fake観察文字長 12〜32; 観察値 'Stock-unlimited fixture', 'Wave 8 admin grid: visible row', 'Wave 8 admin grid: hidden row', 'Wave 8 admin grid: withdrawn row', '管理画面から名称変更した、彩り豊かなジェラートセットです。', '管理画面から作成した商品'; null 1/7。 | Optional | {"minLength":0,"maxLength":128} | Stock-unlimited fixture |
| productStatus | int|null | 商品ステータス - 1=公開（フロント表示）, 2=非公開（フロント非表示）, 3=廃止（論理削除、管理画面でもデフォルト非表示） Fake観察数値 1〜3; 観察値 '1', '2', '3'。 | Required | {"enum":[1,2,3]} | 1 |
| price02 | int|null | 販売価格 - 実際の販売価格（税抜）。税計算・小計計算のベース Fake観察数値 800〜28000。 | Required | {"minimum":0,"maximum":999999999} | 3500 |

#### Links

| Relation | URL |
|----------|-----|
| goProductList | [<code>page://self/products</code>](/products.md) |
## DELETE
doDeleteProduct — soft-delete (status=3). Idempotent replay
surfaces `alreadyDeleted=true`.

**ALPS**: `doDeleteProduct` - 商品を削除する



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| productCode | string | 商品コード（入力） - SKU/品番。在庫管理や受注明細での識別に使用 商品を識別するSKU。Fake corpusではASCII英数・ハイフン中心で、受注明細/カート明細の結合キーになる。 Fake観察文字長 10〜26。 |  | Required | {"minLength":0,"maxLength":64,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | sample-001 |


### Response

[Object: DELETE /admin/product response](../schemas/delete-admin-product.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| alreadyDeleted | boolean|null | 既削除フラグ - /admin/product の処理状態を示す既削除フラグ。画面表示や冪等処理結果の分岐に使う真偽値。 | Required |  |  |
| productName | string|null | 商品名 - 商品の表示名 Fake観察文字長 6〜17。 | Required | {"minLength":0,"maxLength":128} | サンプル商品 A |
| productCode | string | 商品コード - SKU/品番。在庫管理や受注明細での識別に使用 商品を識別するSKU。Fake corpusではASCII英数・ハイフン中心で、受注明細/カート明細の結合キーになる。 Fake観察文字長 10〜26。 | Required | {"minLength":0,"maxLength":64} | sample-001 |
| message | string|null | 商品メッセージ - /admin/product のレスポンスに含まれる処理結果メッセージ。注文時お問い合わせ欄ではなく、画面遷移や完了表示のための通知文。 | Optional | {"minLength":0,"maxLength":32} | 配送は平日希望です。 |
