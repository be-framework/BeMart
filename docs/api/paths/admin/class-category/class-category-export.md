<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/class-category/class-category-export
EC-CUBE 規格分類CSVダウンロード (goExportClassCategory).

GET/POST /admin/class-category/class-category-export → CSV download

`onGet` drives the Be `goExportClassCategory` transition (optionally
scoped to one 規格名); the EC-CUBE-format encoding + download headers
are isolated behind
{@see \MyVendor\BeMart\Be\Reason\Service\ClassCsvCompatibilityInterface}.




## GET
ALPS `goExportClassCategory` に対応する GET 操作。

**ALPS**: `goExportClassCategory` - 規格分類CSVをエクスポートする



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| classNameId | string | 規格名ID（入力） - dtb_class_name.id の不透明な文字列ハンドル。BeMart の ClassNameEntity 層は数値ではなく文字列として保持する。Fake 実装は 32桁hex を生成し、SQL 実装は dtb_class_name.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。非数値 ID は SqlClassNameStorage では miss として扱われ getById / put / remove のいずれも 404 経路（規格名の更新・削除 Final）を踏むため、シードハンドル `nonexistent-zzz` は Fake / SQL 双方で 404 が同形。categoryId / blockId / tagId と同じ Fake↔SQL 二重性 Fake観察文字長 7〜8; 観察値 'cn-color', 'cn-size'。 |  | Optional | {"minLength":0,"maxLength":128,"$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | cn-color |


### Response

[Object: GET /admin/class-category/class-category-export response](../schemas/get-admin-class-category-class-category-export.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| value | string | CSVエクスポート本文 - /admin/class-category/class-category-export が返すCSV本文。列意味はCSV互換サービス側、JSON境界では文字列として契約する。 | Required | {"minLength":0,"maxLength":5000000,"$comment":"CSV\u5217\u306e\u610f\u5473\u691c\u67fb\u306fCSV\u4e92\u63db\u30b5\u30fc\u30d3\u30b9\u3067\u6271\u3044\u3001\u3053\u3053\u3067\u306f\u30ec\u30b9\u30dd\u30f3\u30b9\u672c\u6587\u3068\u3057\u3066\u306e\u6587\u5b57\u5217\u30b5\u30a4\u30ba\u3092\u691c\u67fb\u3059\u308b\u3002"} |  |

#### Links

| Relation | URL |
|----------|-----|
| doImportClassCategoryCsv | [<code>page://self/admin/product/csv-class-category</code>](/admin/product/csv-class-category.md) |