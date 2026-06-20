<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/category/category
EC-CUBE goCategory + doUpdateCategory + doDeleteCategory —
single-row endpoint (Wave 7).

- GET    → goCategory       (admin views one)
  - PUT    → doUpdateCategory (admin edits in place — idempotent)
  - DELETE → doDeleteCategory (admin removes — idempotent)

`categoryId` is the lookup key. The Be Finals enforce the admin
AUTHZ ladder; this resource maps exceptions to HTTP codes.




## GET
ALPS `goCategory` に対応する GET 操作。

**ALPS**: `goCategory`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| categoryId | string | カテゴリID（入力） - dtb_category.id の不透明な文字列ハンドル。BeMart の CategoryEntity 層は数値ではなく文字列として保持する。Fake 実装は 32桁hex を生成し、SQL 実装は dtb_category.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。非数値 ID は SqlCategoryStorage では miss として扱われ getById / put / remove のいずれも 404 経路 (CategoryUpdated / CategoryDeleted / CategoryCreated の親解決) を踏むため、シードハンドル `nonexistent-zzz` は Fake / SQL 双方で 404 が同形。parentId（自己参照 FK parent_category_id）も同じ文字列ハンドルで表現され、非数値 parentId は SQL では NULL（ルート）に倒れる。blockId / pageId / tagId と同じ Fake↔SQL 二重性 Fake観察文字長 8〜10; 観察値 'cat-food', 'cat-drinks'。 |  | Required | {"minLength":0,"maxLength":128,"$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | cat-food |


### Response

[Object: GET /admin/category/category response](../schemas/get-admin-category-category.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| parentId | string|null | 親カテゴリID - /admin/category/category のレスポンスで対象を識別する親カテゴリID。DB採番ID、Fake文字列ID、互換境界IDのどれに該当するかをschemaの型とコメントで分ける。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} |  |
| categoryName | string|null | カテゴリ名 - カテゴリの表示名 Fake観察文字長 4〜6; 観察値 'Food', 'Drinks'。 | Required | {"minLength":0,"maxLength":255} | Food |
| sortNo | int|null | 表示順 - 一覧における並び順 Fake観察数値 1〜20; 観察値 '1', '3', '2', '4', '10', '20'。 | Required | {"minimum":0,"maximum":2147483647} | 1 |
| categoryId | string|null | カテゴリID - dtb_category.id の不透明な文字列ハンドル。BeMart の CategoryEntity 層は数値ではなく文字列として保持する。Fake 実装は 32桁hex を生成し、SQL 実装は dtb_category.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。非数値 ID は SqlCategoryStorage では miss として扱われ getById / put / remove のいずれも 404 経路 (CategoryUpdated / CategoryDeleted / CategoryCreated の親解決) を踏むため、シードハンドル `nonexistent-zzz` は Fake / SQL 双方で 404 が同形。parentId（自己参照 FK parent_category_id）も同じ文字列ハンドルで表現され、非数値 parentId は SQL では NULL（ルート）に倒れる。blockId / pageId / tagId と同じ Fake↔SQL 二重性 Fake観察文字長 8〜10; 観察値 'cat-food', 'cat-drinks'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} | cat-food |

#### Links

| Relation | URL |
|----------|-----|
| goCategoryList | [<code>page://self/admin/category/category-list</code>](/admin/category/category-list.md) |
| doUpdateCategory | [<code>page://self/admin/category/category</code>](/admin/category/category.md) |
| doDeleteCategory | [<code>page://self/admin/category/category</code>](/admin/category/category.md) |
## PUT
ALPS `doUpdateCategory` に対応する PUT 操作。

**ALPS**: `doUpdateCategory`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| categoryId | string | カテゴリID（入力） - dtb_category.id の不透明な文字列ハンドル。BeMart の CategoryEntity 層は数値ではなく文字列として保持する。Fake 実装は 32桁hex を生成し、SQL 実装は dtb_category.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。非数値 ID は SqlCategoryStorage では miss として扱われ getById / put / remove のいずれも 404 経路 (CategoryUpdated / CategoryDeleted / CategoryCreated の親解決) を踏むため、シードハンドル `nonexistent-zzz` は Fake / SQL 双方で 404 が同形。parentId（自己参照 FK parent_category_id）も同じ文字列ハンドルで表現され、非数値 parentId は SQL では NULL（ルート）に倒れる。blockId / pageId / tagId と同じ Fake↔SQL 二重性 Fake観察文字長 8〜10; 観察値 'cat-food', 'cat-drinks'。 |  | Required | {"minLength":0,"maxLength":128,"$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | cat-food |
| categoryName | string | カテゴリ名（入力） - カテゴリの表示名 Fake観察文字長 4〜6; 観察値 'Food', 'Drinks'。 |  | Optional | {"minLength":0,"maxLength":32,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | Food |
| sortNo | int | 表示順（入力） - 一覧における並び順 Fake観察数値 1〜20; 観察値 '1', '3', '2', '4', '10', '20'。 |  | Optional | {"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 1 |
| parentId | string | 親カテゴリID（入力） - /admin/category/category のレスポンスで対象を識別する親カテゴリID。DB採番ID、Fake文字列ID、互換境界IDのどれに該当するかをschemaの型とコメントで分ける。 |  | Optional | {"minLength":0,"maxLength":128,"$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} |  |


### Response

[Object: PUT /admin/category/category response](../schemas/put-admin-category-category.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| parentId | string|null | 親カテゴリID - /admin/category/category のレスポンスで対象を識別する親カテゴリID。DB採番ID、Fake文字列ID、互換境界IDのどれに該当するかをschemaの型とコメントで分ける。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} |  |
| categoryName | string|null | カテゴリ名 - カテゴリの表示名 Fake観察文字長 4〜6; 観察値 'Food', 'Drinks'。 | Required | {"minLength":0,"maxLength":32} | Food |
| sortNo | int|null | 表示順 - 一覧における並び順 Fake観察数値 1〜20; 観察値 '1', '3', '2', '4', '10', '20'。 | Required | {"minimum":0,"maximum":2147483647} | 1 |
| categoryId | string|null | カテゴリID - dtb_category.id の不透明な文字列ハンドル。BeMart の CategoryEntity 層は数値ではなく文字列として保持する。Fake 実装は 32桁hex を生成し、SQL 実装は dtb_category.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。非数値 ID は SqlCategoryStorage では miss として扱われ getById / put / remove のいずれも 404 経路 (CategoryUpdated / CategoryDeleted / CategoryCreated の親解決) を踏むため、シードハンドル `nonexistent-zzz` は Fake / SQL 双方で 404 が同形。parentId（自己参照 FK parent_category_id）も同じ文字列ハンドルで表現され、非数値 parentId は SQL では NULL（ルート）に倒れる。blockId / pageId / tagId と同じ Fake↔SQL 二重性 Fake観察文字長 8〜10; 観察値 'cat-food', 'cat-drinks'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} | cat-food |

#### Links

| Relation | URL |
|----------|-----|
| goCategory | [<code>page://self/admin/category/category</code>](/admin/category/category.md) |
## DELETE
ALPS `doDeleteCategory` に対応する DELETE 操作。

**ALPS**: `doDeleteCategory`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| categoryId | string | カテゴリID（入力） - dtb_category.id の不透明な文字列ハンドル。BeMart の CategoryEntity 層は数値ではなく文字列として保持する。Fake 実装は 32桁hex を生成し、SQL 実装は dtb_category.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。非数値 ID は SqlCategoryStorage では miss として扱われ getById / put / remove のいずれも 404 経路 (CategoryUpdated / CategoryDeleted / CategoryCreated の親解決) を踏むため、シードハンドル `nonexistent-zzz` は Fake / SQL 双方で 404 が同形。parentId（自己参照 FK parent_category_id）も同じ文字列ハンドルで表現され、非数値 parentId は SQL では NULL（ルート）に倒れる。blockId / pageId / tagId と同じ Fake↔SQL 二重性 Fake観察文字長 8〜10; 観察値 'cat-food', 'cat-drinks'。 |  | Required | {"minLength":0,"maxLength":128,"$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | cat-food |


### Response

[Object: DELETE /admin/category/category response](../schemas/delete-admin-category-category.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| categoryId | string|null | カテゴリID - dtb_category.id の不透明な文字列ハンドル。BeMart の CategoryEntity 層は数値ではなく文字列として保持する。Fake 実装は 32桁hex を生成し、SQL 実装は dtb_category.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。非数値 ID は SqlCategoryStorage では miss として扱われ getById / put / remove のいずれも 404 経路 (CategoryUpdated / CategoryDeleted / CategoryCreated の親解決) を踏むため、シードハンドル `nonexistent-zzz` は Fake / SQL 双方で 404 が同形。parentId（自己参照 FK parent_category_id）も同じ文字列ハンドルで表現され、非数値 parentId は SQL では NULL（ルート）に倒れる。blockId / pageId / tagId と同じ Fake↔SQL 二重性 Fake観察文字長 8〜10; 観察値 'cat-food', 'cat-drinks'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} | cat-food |

#### Links

| Relation | URL |
|----------|-----|
| goCategoryList | [<code>page://self/admin/category/category-list</code>](/admin/category/category-list.md) |