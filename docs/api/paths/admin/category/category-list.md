<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/category/category-list
EC-CUBE goCategoryList + doCreateCategory — collection endpoint
(Wave 7).

- GET  → goCategoryList    (admin lists categories — safe read)
  - POST → doCreateCategory  (admin adds a new category)

Single-row affordances (`goCategory`, `doUpdateCategory`,
`doDeleteCategory`) live at `page://self/admin/category/category`.
CSV affordances live at `page://self/admin/category/csv`.

Failure mapping (collapsed admin AUTHZ + CSRF + format):
  - SemanticVariableException             → 400 (parameter format)
  - UnauthorizedAdminAccessException      → 403 (no admin session)
  - CategoryNotFoundException (parentId)  → 404 (referenced parent
                                                 does not exist)
  - CSRF mismatch (POST)                  → 403




## GET
ALPS `goCategoryList` に対応する GET 操作。

**ALPS**: `goCategoryList` - カテゴリ一覧を見る



### Request

_No parameters required_

### Response

[Object: GET /admin/category/category-list response](../schemas/get-admin-category-category-list.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| count | int|null | 件数 - /admin/category/category-list のレスポンスで返す件数。一覧・集計・処理結果の規模を表す非負整数。 | Required | {"minimum":0,"maximum":2147483647} |  |
| categories | array|null | カテゴリ一覧 - /admin/category/category-list のレスポンスで扱うカテゴリ一覧。配列要素はALPS意味とFake観察に基づき、固定できない動的列は例外理由を台帳化する。 | Required | {"items":{"type":["object","null"],"title":"\u30ab\u30c6\u30b4\u30ea","description":"/admin/category/category-list \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u30ab\u30c6\u30b4\u30ea\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `categories` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002","properties":{"parentId":{"type":["string","null"],"title":"\u89aa\u30ab\u30c6\u30b4\u30eaID","description":"/admin/category/category-list \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u3067\u5bfe\u8c61\u3092\u8b58\u5225\u3059\u308b\u89aa\u30ab\u30c6\u30b4\u30eaID\u3002DB\u63a1\u756aID\u3001Fake\u6587\u5b57\u5217ID\u3001\u4e92\u63db\u5883\u754cID\u306e\u3069\u308c\u306b\u8a72\u5f53\u3059\u308b\u304b\u3092schema\u306e\u578b\u3068\u30b3\u30e1\u30f3\u30c8\u3067\u5206\u3051\u308b\u3002","minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"},"categoryName":{"type":["string","null"],"minLength":0,"maxLength":255,"title":"\u30ab\u30c6\u30b4\u30ea\u540d","description":"\u30ab\u30c6\u30b4\u30ea\u306e\u8868\u793a\u540d Fake\u89b3\u5bdf\u6587\u5b57\u9577 4\u301c6; \u89b3\u5bdf\u5024 'Food', 'Drinks'\u3002","example":"Food"},"sortNo":{"type":["integer","null"],"minimum":0,"maximum":2147483647,"title":"\u8868\u793a\u9806","description":"\u4e00\u89a7\u306b\u304a\u3051\u308b\u4e26\u3073\u9806 Fake\u89b3\u5bdf\u6570\u5024 1\u301c20; \u89b3\u5bdf\u5024 '1', '3', '2', '4', '10', '20'\u3002","example":1},"categoryId":{"type":["string","null"],"title":"\u30ab\u30c6\u30b4\u30eaID","description":"dtb_category.id \u306e\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217\u30cf\u30f3\u30c9\u30eb\u3002BeMart \u306e CategoryEntity \u5c64\u306f\u6570\u5024\u3067\u306f\u306a\u304f\u6587\u5b57\u5217\u3068\u3057\u3066\u4fdd\u6301\u3059\u308b\u3002Fake \u5b9f\u88c5\u306f 32\u6841hex \u3092\u751f\u6210\u3057\u3001SQL \u5b9f\u88c5\u306f dtb_category.id (int unsigned AUTO_INCREMENT) \u3092\u6587\u5b57\u5217\u5316\u3057\u3066\u4f7f\u7528\uff08\u540c\u30a4\u30f3\u30bf\u30fc\u30d5\u30a7\u30a4\u30b9\u30fb\u7570 ID \u5f62\u72b6\uff09\u3002\u975e\u6570\u5024 ID \u306f SqlCategoryStorage \u3067\u306f miss \u3068\u3057\u3066\u6271\u308f\u308c getById / put / remove \u306e\u3044\u305a\u308c\u3082 404 \u7d4c\u8def (CategoryUpdated / CategoryDeleted / CategoryCreated \u306e\u89aa\u89e3\u6c7a) \u3092\u8e0f\u3080\u305f\u3081\u3001\u30b7\u30fc\u30c9\u30cf\u30f3\u30c9\u30eb `nonexistent-zzz` \u306f Fake / SQL \u53cc\u65b9\u3067 404 \u304c\u540c\u5f62\u3002parentId\uff08\u81ea\u5df1\u53c2\u7167 FK parent_category_id\uff09\u3082\u540c\u3058\u6587\u5b57\u5217\u30cf\u30f3\u30c9\u30eb\u3067\u8868\u73fe\u3055\u308c\u3001\u975e\u6570\u5024 parentId \u306f SQL \u3067\u306f NULL\uff08\u30eb\u30fc\u30c8\uff09\u306b\u5012\u308c\u308b\u3002blockId / pageId / tagId \u3068\u540c\u3058 Fake\u2194SQL \u4e8c\u91cd\u6027 Fake\u89b3\u5bdf\u6587\u5b57\u9577 8\u301c10; \u89b3\u5bdf\u5024 'cat-food', 'cat-drinks'\u3002","example":"cat-food","minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"}},"additionalProperties":false,"$comment":"\u914d\u5217\u8981\u7d20\u306fFake/Resource\u3067\u89b3\u5bdf\u3055\u308c\u305f\u65e2\u77e5property\u306b\u56fa\u5b9a\u3059\u308b\u3002\u65b0\u3057\u3044\u5217\u304c\u5fc5\u8981\u306b\u306a\u3063\u305f\u5834\u5408\u306fSemantic-Ex\u89b3\u5bdf\u306b\u8ffd\u52a0\u3057\u3066schema\u3092\u66f4\u65b0\u3059\u308b\u3002"},"minItems":0} |  |

#### Links

| Relation | URL |
|----------|-----|
| doCreateCategory | [<code>page://self/admin/category/category-list</code>](/admin/category/category-list.md) |
| goCategory | [<code>page://self/admin/category/category</code>](/admin/category/category.md) |
| doUpdateCategory | [<code>page://self/admin/category/category</code>](/admin/category/category.md) |
| doDeleteCategory | [<code>page://self/admin/category/category</code>](/admin/category/category.md) |
| doImportCategoryCsv | [<code>page://self/admin/category/csv</code>](/admin/category/csv.md) |
| goExportCategory | [<code>page://self/admin/category/csv</code>](/admin/category/csv.md) |
## POST
ALPS `doCreateCategory` に対応する POST 操作。

**ALPS**: `doCreateCategory` - カテゴリを作成する



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| categoryName | string | カテゴリ名（入力） - カテゴリの表示名 Fake観察文字長 4〜6; 観察値 'Food', 'Drinks'。 |  | Required | {"minLength":0,"maxLength":32,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | Food |
| sortNo | int | 表示順（入力） - 一覧における並び順 Fake観察数値 1〜20; 観察値 '1', '3', '2', '4', '10', '20'。 |  | Required | {"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 1 |
| parentId | string | 親カテゴリID（入力） - /admin/category/category-list のレスポンスで対象を識別する親カテゴリID。DB採番ID、Fake文字列ID、互換境界IDのどれに該当するかをschemaの型とコメントで分ける。 |  | Optional | {"minLength":0,"maxLength":128,"$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} |  |


### Response

[Object: POST /admin/category/category-list response](../schemas/post-admin-category-category-list.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| parentId | string|null | 親カテゴリID - /admin/category/category-list のレスポンスで対象を識別する親カテゴリID。DB採番ID、Fake文字列ID、互換境界IDのどれに該当するかをschemaの型とコメントで分ける。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} |  |
| categoryName | string|null | カテゴリ名 - カテゴリの表示名 Fake観察文字長 4〜6; 観察値 'Food', 'Drinks'。 | Required | {"minLength":0,"maxLength":32} | Food |
| sortNo | int|null | 表示順 - 一覧における並び順 Fake観察数値 1〜20; 観察値 '1', '3', '2', '4', '10', '20'。 | Required | {"minimum":0,"maximum":2147483647} | 1 |
| categoryId | string|null | カテゴリID - dtb_category.id の不透明な文字列ハンドル。BeMart の CategoryEntity 層は数値ではなく文字列として保持する。Fake 実装は 32桁hex を生成し、SQL 実装は dtb_category.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。非数値 ID は SqlCategoryStorage では miss として扱われ getById / put / remove のいずれも 404 経路 (CategoryUpdated / CategoryDeleted / CategoryCreated の親解決) を踏むため、シードハンドル `nonexistent-zzz` は Fake / SQL 双方で 404 が同形。parentId（自己参照 FK parent_category_id）も同じ文字列ハンドルで表現され、非数値 parentId は SQL では NULL（ルート）に倒れる。blockId / pageId / tagId と同じ Fake↔SQL 二重性 Fake観察文字長 8〜10; 観察値 'cat-food', 'cat-drinks'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} | cat-food |

#### Links

| Relation | URL |
|----------|-----|
| goCategoryList | [<code>page://self/admin/category/category-list</code>](/admin/category/category-list.md) |