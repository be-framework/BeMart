<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/block/block-list
EC-CUBE goBlockList + doCreateBlock — collection endpoint (Wave 9 CMS).






## GET
ALPS `goBlockList` に対応する GET 操作。

**ALPS**: `goBlockList`



### Request

_No parameters required_

### Response

[Object: GET /admin/block/block-list response](../schemas/get-admin-block-block-list.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| count | int|null | 件数 - /admin/block/block-list のレスポンスで返す件数。一覧・集計・処理結果の規模を表す非負整数。 | Required | {"minimum":0,"maximum":2147483647} |  |
| blocks | array|null | ブロック一覧 - /admin/block/block-list のレスポンスで扱うブロック一覧。配列要素はALPS意味とFake観察に基づき、固定できない動的列は例外理由を台帳化する。 | Required | {"items":{"type":["object","null"],"title":"\u30d6\u30ed\u30c3\u30af","description":"/admin/block/block-list \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u30d6\u30ed\u30c3\u30af\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `blocks` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002","properties":{"blockDeletable":{"type":["boolean","null"],"minLength":0,"maxLength":255,"title":"\u30d6\u30ed\u30c3\u30af\u524a\u9664\u53ef\u80fd\u30d5\u30e9\u30b0","description":"\u3053\u306e\u30d6\u30ed\u30c3\u30af\u3092\u7ba1\u7406\u753b\u9762\u304b\u3089\u524a\u9664\u3067\u304d\u308b\u304b\u3002\u30b7\u30b9\u30c6\u30e0\u6a19\u6e96\u30d6\u30ed\u30c3\u30af\u306f\u524a\u9664\u4e0d\u53ef \u89b3\u5bdf\u5024 'false', 'true'\u3002","example":"false"},"blockName":{"type":["string","null"],"minLength":0,"maxLength":32,"title":"\u30d6\u30ed\u30c3\u30af\u540d","description":"\u30d6\u30ed\u30c3\u30af\u306e\u8868\u793a\u540d Fake\u89b3\u5bdf\u6587\u5b57\u9577 4\u301c8; \u89b3\u5bdf\u5024 '\u30d8\u30c3\u30c0\u30fc', '\u30e6\u30fc\u30b6\u30fc\u30d6\u30ed\u30c3\u30af'\u3002","example":"\u30d8\u30c3\u30c0\u30fc"},"blockId":{"type":["string","null"],"title":"\u30d6\u30ed\u30c3\u30afID","description":"dtb_block.id \u306e\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217\u30cf\u30f3\u30c9\u30eb\u3002BeMart \u306e BlockEntity \u5c64\u306f\u6570\u5024\u3067\u306f\u306a\u304f\u6587\u5b57\u5217\u3068\u3057\u3066\u4fdd\u6301\u3059\u308b\u3002Fake \u5b9f\u88c5\u306f `bk-` \u30d7\u30ec\u30d5\u30a3\u30c3\u30af\u30b9\u4ed8\u304d\u306e\u82f1\u6570\u5b57\u3092\u751f\u6210\u3057\uff08\u30b7\u30fc\u30c9 `bk-header` \u3092\u542b\u3080\uff09\u3001SQL \u5b9f\u88c5\u306f dtb_block.id (int unsigned AUTO_INCREMENT) \u3092\u6587\u5b57\u5217\u5316\u3057\u3066\u4f7f\u7528\uff08\u540c\u30a4\u30f3\u30bf\u30fc\u30d5\u30a7\u30a4\u30b9\u30fb\u7570 ID \u5f62\u72b6\uff09\u3002\u975e\u6570\u5024 ID \u306f SqlBlockStorage \u3067\u306f miss \u3068\u3057\u3066\u6271\u308f\u308c getById / put / remove \u306e\u3044\u305a\u308c\u3082 404 \u7d4c\u8def (BlockUpdated / BlockDeleted) \u3092\u8e0f\u3080\u305f\u3081\u3001\u30b7\u30fc\u30c9\u30cf\u30f3\u30c9\u30eb `bk-header` \u3084 `nonexistent-zzz` \u306f Fake / SQL \u53cc\u65b9\u3067 404 \u304c\u540c\u5f62 Fake\u89b3\u5bdf\u6587\u5b57\u9577 7\u301c9; \u89b3\u5bdf\u5024 'bk-header', 'bk-user'\u3002","example":"bk-header","minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"},"blockFileName":{"type":["string","null"],"minLength":0,"maxLength":32,"title":"\u30d6\u30ed\u30c3\u30af\u30d5\u30a1\u30a4\u30eb\u540d","description":"\u30d6\u30ed\u30c3\u30af\u306e\u30c6\u30f3\u30d7\u30ec\u30fc\u30c8\u30d5\u30a1\u30a4\u30eb\u540d Fake\u89b3\u5bdf\u6587\u5b57\u9577 6\u301c10; \u89b3\u5bdf\u5024 'header', 'user_block'\u3002","example":"header"}},"additionalProperties":false,"$comment":"\u914d\u5217\u8981\u7d20\u306fFake/Resource\u3067\u89b3\u5bdf\u3055\u308c\u305f\u65e2\u77e5property\u306b\u56fa\u5b9a\u3059\u308b\u3002\u65b0\u3057\u3044\u5217\u304c\u5fc5\u8981\u306b\u306a\u3063\u305f\u5834\u5408\u306fSemantic-Ex\u89b3\u5bdf\u306b\u8ffd\u52a0\u3057\u3066schema\u3092\u66f4\u65b0\u3059\u308b\u3002"},"minItems":0} |  |

#### Links

| Relation | URL |
|----------|-----|
| doCreateBlock | [<code>page://self/admin/block/block-list</code>](/admin/block/block-list.md) |
| doUpdateBlock | [<code>page://self/admin/block/block</code>](/admin/block/block.md) |
| doDeleteBlock | [<code>page://self/admin/block/block</code>](/admin/block/block.md) |
## POST
ALPS `doCreateBlock` に対応する POST 操作。

**ALPS**: `doCreateBlock`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| blockName | string | ブロック名（入力） - ブロックの表示名 Fake観察文字長 4〜8; 観察値 'ヘッダー', 'ユーザーブロック'。 |  | Required | {"minLength":0,"maxLength":32,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | ヘッダー |
| blockFileName | string | ブロックファイル名（入力） - ブロックのテンプレートファイル名 Fake観察文字長 6〜10; 観察値 'header', 'user_block'。 |  | Required | {"minLength":0,"maxLength":32,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | header |


### Response

[Object: POST /admin/block/block-list response](../schemas/post-admin-block-block-list.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| blockDeletable | boolean|null | ブロック削除可能フラグ - このブロックを管理画面から削除できるか。システム標準ブロックは削除不可 観察値 'false', 'true'。 | Required | {"minLength":0,"maxLength":255} | false |
| blockName | string|null | ブロック名 - ブロックの表示名 Fake観察文字長 4〜8; 観察値 'ヘッダー', 'ユーザーブロック'。 | Required | {"minLength":0,"maxLength":32} | ヘッダー |
| blockId | string|null | ブロックID - dtb_block.id の不透明な文字列ハンドル。BeMart の BlockEntity 層は数値ではなく文字列として保持する。Fake 実装は `bk-` プレフィックス付きの英数字を生成し（シード `bk-header` を含む）、SQL 実装は dtb_block.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。非数値 ID は SqlBlockStorage では miss として扱われ getById / put / remove のいずれも 404 経路 (BlockUpdated / BlockDeleted) を踏むため、シードハンドル `bk-header` や `nonexistent-zzz` は Fake / SQL 双方で 404 が同形 Fake観察文字長 7〜9; 観察値 'bk-header', 'bk-user'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} | bk-header |
| blockFileName | string|null | ブロックファイル名 - ブロックのテンプレートファイル名 Fake観察文字長 6〜10; 観察値 'header', 'user_block'。 | Required | {"minLength":0,"maxLength":32} | header |

#### Links

| Relation | URL |
|----------|-----|
| goBlockList | [<code>page://self/admin/block/block-list</code>](/admin/block/block-list.md) |