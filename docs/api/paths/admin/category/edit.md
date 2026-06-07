<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/category/edit
EC-CUBE カテゴリ登録 / カテゴリ編集 — Product Tier-2
(`admin/Product/category.twig`, the category tree-list + inline
add/edit screen).

GET /admin/category/edit                 → tree list + blank "new" form
  GET /admin/category/edit?categoryId=…    → tree list + form pre-filled

Thin GET renderer. The sibling JSON resources
{@see \MyVendor\BeMart\Resource\Page\Admin\Category\CategoryList}
(collection + create) and {@see \MyVendor\BeMart\Resource\Page\Admin\Category\Category}
(update / delete) carry the writes; this resource is the HTML editor
shell — it renders the category tree alongside the add/edit form. An
empty `$categoryId` renders the blank "新規カテゴリ" form (the
render-smoke test exercises this with empty JSON-backed fake storage); a known
categoryId pre-fills; an unknown categoryId is 404.

AUTHZ delegates to the Be Finals, which raise
{@see \UnauthorizedAdminAccessException} for a non-admin firewall —
surfaced as 403.




## GET
ALPS `goCategory` に対応する GET 操作。

**ALPS**: `goCategory`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| categoryId | string | カテゴリID（入力） - dtb_category.id の不透明な文字列ハンドル。BeMart の CategoryEntity 層は数値ではなく文字列として保持する。Fake 実装は 32桁hex を生成し、SQL 実装は dtb_category.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。非数値 ID は SqlCategoryStorage では miss として扱われ getById / put / remove のいずれも 404 経路 (CategoryUpdated / CategoryDeleted / CategoryCreated の親解決) を踏むため、シードハンドル `nonexistent-zzz` は Fake / SQL 双方で 404 が同形。parentId（自己参照 FK parent_category_id）も同じ文字列ハンドルで表現され、非数値 parentId は SQL では NULL（ルート）に倒れる。blockId / pageId / tagId と同じ Fake↔SQL 二重性 Fake観察文字長 8〜10; 観察値 'cat-food', 'cat-drinks'。 |  | Optional | {"default":"","minLength":0,"maxLength":128,"$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | cat-food |


### Response

[Object: GET /admin/category/edit response](../schemas/get-admin-category-edit.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| form | object|array|null | 入力フォーム - Aura/WebForm由来のフォームオブジェクト。フレームワーク内部構造のためschemaでは存在と型のみを契約する。 | Optional | {"$comment":"Aura/WebForm\u7531\u6765\u306e\u4e0d\u900f\u660e\u30d5\u30a9\u30fc\u30e0\u8868\u73fe\u3002Resource\u5883\u754c\u3067\u306f\u30d5\u30a9\u30fc\u30e0\u306e\u5b58\u5728\u3068\u30b3\u30f3\u30c6\u30ad\u30b9\u30c8\u3060\u3051\u3092\u5951\u7d04\u3057\u3001\u5185\u90e8\u69cb\u9020\u306f\u30d5\u30ec\u30fc\u30e0\u30ef\u30fc\u30af\u5883\u754c\u306b\u59d4\u306d\u308b\u305f\u3081\u8ffd\u52a0\u30ad\u30fc\u5236\u7d04\u3092\u7f6e\u304b\u306a\u3044\u3002"} |  |
| categories | array|null | カテゴリ一覧 - /admin/category/edit のレスポンスで扱うカテゴリ一覧。配列要素はALPS意味とFake観察に基づき、固定できない動的列は例外理由を台帳化する。 | Required | {"items":{"type":["object","null"],"title":"\u30ab\u30c6\u30b4\u30ea","description":"/admin/category/edit \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u30ab\u30c6\u30b4\u30ea\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `categories` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002","properties":{"parentId":{"type":["string","null"],"title":"\u89aa\u30ab\u30c6\u30b4\u30eaID","description":"/admin/category/edit \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u3067\u5bfe\u8c61\u3092\u8b58\u5225\u3059\u308b\u89aa\u30ab\u30c6\u30b4\u30eaID\u3002DB\u63a1\u756aID\u3001Fake\u6587\u5b57\u5217ID\u3001\u4e92\u63db\u5883\u754cID\u306e\u3069\u308c\u306b\u8a72\u5f53\u3059\u308b\u304b\u3092schema\u306e\u578b\u3068\u30b3\u30e1\u30f3\u30c8\u3067\u5206\u3051\u308b\u3002","minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"},"categoryName":{"type":["string","null"],"minLength":0,"maxLength":32,"title":"\u30ab\u30c6\u30b4\u30ea\u540d","description":"\u30ab\u30c6\u30b4\u30ea\u306e\u8868\u793a\u540d Fake\u89b3\u5bdf\u6587\u5b57\u9577 4\u301c6; \u89b3\u5bdf\u5024 'Food', 'Drinks'\u3002","example":"Food"},"sortNo":{"type":["integer","null"],"minimum":0,"maximum":2147483647,"title":"\u8868\u793a\u9806","description":"\u4e00\u89a7\u306b\u304a\u3051\u308b\u4e26\u3073\u9806 Fake\u89b3\u5bdf\u6570\u5024 1\u301c20; \u89b3\u5bdf\u5024 '1', '3', '2', '4', '10', '20'\u3002","example":1},"categoryId":{"type":["string","null"],"title":"\u30ab\u30c6\u30b4\u30eaID","description":"dtb_category.id \u306e\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217\u30cf\u30f3\u30c9\u30eb\u3002BeMart \u306e CategoryEntity \u5c64\u306f\u6570\u5024\u3067\u306f\u306a\u304f\u6587\u5b57\u5217\u3068\u3057\u3066\u4fdd\u6301\u3059\u308b\u3002Fake \u5b9f\u88c5\u306f 32\u6841hex \u3092\u751f\u6210\u3057\u3001SQL \u5b9f\u88c5\u306f dtb_category.id (int unsigned AUTO_INCREMENT) \u3092\u6587\u5b57\u5217\u5316\u3057\u3066\u4f7f\u7528\uff08\u540c\u30a4\u30f3\u30bf\u30fc\u30d5\u30a7\u30a4\u30b9\u30fb\u7570 ID \u5f62\u72b6\uff09\u3002\u975e\u6570\u5024 ID \u306f SqlCategoryStorage \u3067\u306f miss \u3068\u3057\u3066\u6271\u308f\u308c getById / put / remove \u306e\u3044\u305a\u308c\u3082 404 \u7d4c\u8def (CategoryUpdated / CategoryDeleted / CategoryCreated \u306e\u89aa\u89e3\u6c7a) \u3092\u8e0f\u3080\u305f\u3081\u3001\u30b7\u30fc\u30c9\u30cf\u30f3\u30c9\u30eb `nonexistent-zzz` \u306f Fake / SQL \u53cc\u65b9\u3067 404 \u304c\u540c\u5f62\u3002parentId\uff08\u81ea\u5df1\u53c2\u7167 FK parent_category_id\uff09\u3082\u540c\u3058\u6587\u5b57\u5217\u30cf\u30f3\u30c9\u30eb\u3067\u8868\u73fe\u3055\u308c\u3001\u975e\u6570\u5024 parentId \u306f SQL \u3067\u306f NULL\uff08\u30eb\u30fc\u30c8\uff09\u306b\u5012\u308c\u308b\u3002blockId / pageId / tagId \u3068\u540c\u3058 Fake\u2194SQL \u4e8c\u91cd\u6027 Fake\u89b3\u5bdf\u6587\u5b57\u9577 8\u301c10; \u89b3\u5bdf\u5024 'cat-food', 'cat-drinks'\u3002","example":"cat-food","minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"}},"additionalProperties":false,"$comment":"\u914d\u5217\u8981\u7d20\u306fFake/Resource\u3067\u89b3\u5bdf\u3055\u308c\u305f\u65e2\u77e5property\u306b\u56fa\u5b9a\u3059\u308b\u3002\u65b0\u3057\u3044\u5217\u304c\u5fc5\u8981\u306b\u306a\u3063\u305f\u5834\u5408\u306fSemantic-Ex\u89b3\u5bdf\u306b\u8ffd\u52a0\u3057\u3066schema\u3092\u66f4\u65b0\u3059\u308b\u3002"},"minItems":0} |  |
| category | array|null|object | カテゴリ詳細 - /admin/category/edit のレスポンスで扱うカテゴリ詳細。配列要素はALPS意味とFake観察に基づき、固定できない動的列は例外理由を台帳化する。 | Optional | {"items":{"type":"string","title":"\u30ab\u30c6\u30b4\u30ea","minLength":0,"maxLength":255,"description":"/admin/category/edit \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u30ab\u30c6\u30b4\u30ea\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `category` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002"},"minItems":0,"$comment":"\u5358\u4e00\u8a73\u7d30\u753b\u9762\u3067\u306f\u672a\u9078\u629e/\u521d\u671f\u8868\u793a\u306b\u7a7a\u914d\u5217\u3001\u53d6\u5f97\u6e08\u307f\u72b6\u614b\u306bobject\u304c\u73fe\u308c\u308b\u3002\u4e0d\u900f\u660e\u306a\u8a73\u7d30\u69cb\u9020\u306f\u65e2\u77e5property\u3092\u512a\u5148\u3057\u3001\u8ffd\u52a0\u30ad\u30fc\u306f\u4e92\u63db\u5883\u754c\u3068\u3057\u3066\u8a31\u5bb9\u3059\u308b\u3002"} |  |
| count | int|null | 件数 - /admin/category/edit のレスポンスで返す件数。一覧・集計・処理結果の規模を表す非負整数。 | Required | {"minimum":0,"maximum":2147483647} |  |
| categoryId | string|null | カテゴリID - dtb_category.id の不透明な文字列ハンドル。BeMart の CategoryEntity 層は数値ではなく文字列として保持する。Fake 実装は 32桁hex を生成し、SQL 実装は dtb_category.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。非数値 ID は SqlCategoryStorage では miss として扱われ getById / put / remove のいずれも 404 経路 (CategoryUpdated / CategoryDeleted / CategoryCreated の親解決) を踏むため、シードハンドル `nonexistent-zzz` は Fake / SQL 双方で 404 が同形。parentId（自己参照 FK parent_category_id）も同じ文字列ハンドルで表現され、非数値 parentId は SQL では NULL（ルート）に倒れる。blockId / pageId / tagId と同じ Fake↔SQL 二重性 Fake観察文字長 8〜10; 観察値 'cat-food', 'cat-drinks'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} | cat-food |

#### Links

| Relation | URL |
|----------|-----|
| doCreateCategory | [<code>page://self/admin/category/category-list</code>](/admin/category/category-list.md) |
| doUpdateCategory | [<code>page://self/admin/category/category</code>](/admin/category/category.md) |
| goCategoryList | [<code>page://self/admin/category/category-list</code>](/admin/category/category-list.md) |