<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/tag/tag
EC-CUBE doDeleteTag — single-row endpoint (Wave 9). ALPS exposes
neither doUpdateTag nor goTag — only DELETE.






## DELETE
ALPS `doDeleteTag` に対応する DELETE 操作。

**ALPS**: `doDeleteTag` - タグを削除する



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| tagId | string | タグID（入力） - dtb_tag.id の不透明な文字列ハンドル。BeMart の TagEntity 層は数値ではなく文字列として保持する。Fake 実装は `tg-` プレフィックス付きの英数字を生成し、SQL 実装は dtb_tag.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。非数値 ID は SqlTagStorage では miss として扱われ getById / put / remove のいずれも 404 経路 (TagDeleted) を踏むため、シードハンドル `tg-new` / `tg-sale` は Fake 専用 Fake観察文字長 6〜7; 観察値 'tg-new', 'tg-sale'。 |  | Required | {"minLength":0,"maxLength":128,"$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | tg-new |


### Response

[Object: DELETE /admin/tag/tag response](../schemas/delete-admin-tag-tag.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| tagId | string|null | タグID - dtb_tag.id の不透明な文字列ハンドル。BeMart の TagEntity 層は数値ではなく文字列として保持する。Fake 実装は `tg-` プレフィックス付きの英数字を生成し、SQL 実装は dtb_tag.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。非数値 ID は SqlTagStorage では miss として扱われ getById / put / remove のいずれも 404 経路 (TagDeleted) を踏むため、シードハンドル `tg-new` / `tg-sale` は Fake 専用 Fake観察文字長 6〜7; 観察値 'tg-new', 'tg-sale'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} | tg-new |

#### Links

| Relation | URL |
|----------|-----|
| goTagList | [<code>page://self/admin/tag/tag-list</code>](/admin/tag/tag-list.md) |