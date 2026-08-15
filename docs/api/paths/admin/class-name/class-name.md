<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/class-name/class-name
EC-CUBE doUpdateClassName + doDeleteClassName — single-row endpoint
(Wave 7).

- PUT    → doUpdateClassName (admin renames an axis — idempotent)
- DELETE → doDeleteClassName (admin removes an axis — idempotent)




## PUT
ALPS `doUpdateClassName` に対応する PUT 操作。

**ALPS**: `doUpdateClassName` - 規格名を更新する



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| classNameId | string | 規格名ID（入力） - dtb_class_name.id の不透明な文字列ハンドル。BeMart の ClassNameEntity 層は数値ではなく文字列として保持する。Fake 実装は 32桁hex を生成し、SQL 実装は dtb_class_name.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。非数値 ID は SqlClassNameStorage では miss として扱われ getById / put / remove のいずれも 404 経路（規格名の更新・削除 Final）を踏むため、シードハンドル `nonexistent-zzz` は Fake / SQL 双方で 404 が同形。categoryId / blockId / tagId と同じ Fake↔SQL 二重性 Fake観察文字長 7〜8; 観察値 'cn-color', 'cn-size'。 |  | Required | {"minLength":0,"maxLength":128,"$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | cn-color |
| classNameLabel | string | 規格名（入力） - 商品バリエーション軸の名前（例: カラー、サイズ）。EC-CUBEの"class"はOOPのクラスではなく商品規格を意味する |  | Optional | {"minLength":0,"maxLength":255,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} |  |


### Response

[Object: PUT /admin/class-name/class-name response](../schemas/put-admin-class-name-class-name.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| name | string|null | 処理表示名 - Fake観察文字長 1〜7; 観察値 'テスト管理者', '副管理者', '店舗オーナー', '削除済み管理者', 'Red', 'Blue', 'S', 'Color'。 | Required | {"minLength":0,"maxLength":32} | テスト管理者 |
| classNameId | string|null | 規格名ID - dtb_class_name.id の不透明な文字列ハンドル。BeMart の ClassNameEntity 層は数値ではなく文字列として保持する。Fake 実装は 32桁hex を生成し、SQL 実装は dtb_class_name.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。非数値 ID は SqlClassNameStorage では miss として扱われ getById / put / remove のいずれも 404 経路（規格名の更新・削除 Final）を踏むため、シードハンドル `nonexistent-zzz` は Fake / SQL 双方で 404 が同形。categoryId / blockId / tagId と同じ Fake↔SQL 二重性 Fake観察文字長 7〜8; 観察値 'cn-color', 'cn-size'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} | cn-color |

#### Links

| Relation | URL |
|----------|-----|
| goClassNameList | [<code>page://self/admin/class-name/class-name-list</code>](/admin/class-name/class-name-list.md) |
## DELETE
ALPS `doDeleteClassName` に対応する DELETE 操作。

**ALPS**: `doDeleteClassName` - 規格名を削除する



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| classNameId | string | 規格名ID（入力） - dtb_class_name.id の不透明な文字列ハンドル。BeMart の ClassNameEntity 層は数値ではなく文字列として保持する。Fake 実装は 32桁hex を生成し、SQL 実装は dtb_class_name.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。非数値 ID は SqlClassNameStorage では miss として扱われ getById / put / remove のいずれも 404 経路（規格名の更新・削除 Final）を踏むため、シードハンドル `nonexistent-zzz` は Fake / SQL 双方で 404 が同形。categoryId / blockId / tagId と同じ Fake↔SQL 二重性 Fake観察文字長 7〜8; 観察値 'cn-color', 'cn-size'。 |  | Required | {"minLength":0,"maxLength":128,"$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | cn-color |


### Response

[Object: DELETE /admin/class-name/class-name response](../schemas/delete-admin-class-name-class-name.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| classNameId | string|null | 規格名ID - dtb_class_name.id の不透明な文字列ハンドル。BeMart の ClassNameEntity 層は数値ではなく文字列として保持する。Fake 実装は 32桁hex を生成し、SQL 実装は dtb_class_name.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。非数値 ID は SqlClassNameStorage では miss として扱われ getById / put / remove のいずれも 404 経路（規格名の更新・削除 Final）を踏むため、シードハンドル `nonexistent-zzz` は Fake / SQL 双方で 404 が同形。categoryId / blockId / tagId と同じ Fake↔SQL 二重性 Fake観察文字長 7〜8; 観察値 'cn-color', 'cn-size'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} | cn-color |

#### Links

| Relation | URL |
|----------|-----|
| goClassNameList | [<code>page://self/admin/class-name/class-name-list</code>](/admin/class-name/class-name-list.md) |