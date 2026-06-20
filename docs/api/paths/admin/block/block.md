<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/block/block
EC-CUBE doUpdateBlock + doDeleteBlock — single-row endpoint (Wave 9).

ALPS has no goBlock — the admin edits a block from the list view
directly. Only PUT and DELETE are exposed here for the domain.

Phase 3 — HTML FORM page. `onGet` exposes an {@see \AdminBlockForm}
(Ray.WebFormModule AbstractForm) as `body['form']` so the admin block
edit page (`Content/block_edit.twig` port) can render real `<input>`s
via `{{ form.input(...) }}`.

`onGet` renders the NEW-block form when no blockId is supplied, and
pre-fills the edit form when a blockId is supplied.




## GET
Renders the block edit form.

The JSON contexts (`app`, `prod`, `test`) ignore `body['form']`.

**ALPS**: `goBlock`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| blockId | string | ブロックID（入力） - dtb_block.id の不透明な文字列ハンドル。SQL 実装では dtb_block.id を文字列化して使用し、Fake 実装では bk-* seed handle を使用する。 |  | Optional | {"minLength":0,"maxLength":128} | 1 |


### Response

[Object: GET /admin/block/block response](../schemas/get-admin-block-block.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| blockId | string | ブロックID - dtb_block.id の不透明な文字列ハンドル。新規作成フォームでは空文字、編集フォームでは既存ブロックID。 | Optional | {"minLength":0,"maxLength":128} | 1 |
| blockName | string | ブロック名 - ブロックの表示名。新規作成フォームでは空文字、編集フォームでは保存済み値。 | Optional | {"minLength":0,"maxLength":32} |  |
| blockFileName | string | ブロックファイル名 - ブロックのテンプレートファイル名。新規作成フォームでは空文字、編集フォームでは保存済み値。 | Optional | {"minLength":0,"maxLength":32} |  |
| blockDeletable | boolean | 削除可否 - 管理画面で削除可能なユーザー定義ブロックかどうか。 | Optional |  |  |
| form | object|array|null | 入力フォーム - Aura/WebForm由来のフォームオブジェクト。フレームワーク内部構造のためschemaでは存在と型のみを契約する。 | Optional | {"$comment":"Aura/WebForm\u7531\u6765\u306e\u4e0d\u900f\u660e\u30d5\u30a9\u30fc\u30e0\u8868\u73fe\u3002Resource\u5883\u754c\u3067\u306f\u30d5\u30a9\u30fc\u30e0\u306e\u5b58\u5728\u3068\u30b3\u30f3\u30c6\u30ad\u30b9\u30c8\u3060\u3051\u3092\u5951\u7d04\u3057\u3001\u5185\u90e8\u69cb\u9020\u306f\u30d5\u30ec\u30fc\u30e0\u30ef\u30fc\u30af\u5883\u754c\u306b\u59d4\u306d\u308b\u305f\u3081\u8ffd\u52a0\u30ad\u30fc\u5236\u7d04\u3092\u7f6e\u304b\u306a\u3044\u3002"} |  |
| csrfToken | string|null | 処理識別子 - フォーム送信の偽造を防ぐために送信元画面で発行されるトークン。Fake環境では deterministic な値を使う。 | Optional | {"minLength":8,"maxLength":160,"pattern":"^[A-Za-z0-9_.:-]+$"} | fake-csrf-token-bemart-2026 |

#### Links

| Relation | URL |
|----------|-----|
| goBlockList | [<code>page://self/admin/block/block-list</code>](/admin/block/block-list.md) |
| doCreateBlock | [<code>page://self/admin/block/block-list</code>](/admin/block/block-list.md) |
| doUpdateBlock | [<code>page://self/admin/block/block</code>](/admin/block/block.md) |
## PUT
ALPS `doUpdateBlock` に対応する PUT 操作。

**ALPS**: `doUpdateBlock`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| blockId | string | ブロックID（入力） - dtb_block.id の不透明な文字列ハンドル。BeMart の BlockEntity 層は数値ではなく文字列として保持する。Fake 実装は `bk-` プレフィックス付きの英数字を生成し（シード `bk-header` を含む）、SQL 実装は dtb_block.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。非数値 ID は SqlBlockStorage では miss として扱われ getById / put / remove のいずれも 404 経路 (BlockUpdated / BlockDeleted) を踏むため、シードハンドル `bk-header` や `nonexistent-zzz` は Fake / SQL 双方で 404 が同形 Fake観察文字長 7〜9; 観察値 'bk-header', 'bk-user'。 |  | Required | {"minLength":0,"maxLength":128,"$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | bk-header |
| blockName | string | ブロック名（入力） - ブロックの表示名 Fake観察文字長 4〜8; 観察値 'ヘッダー', 'ユーザーブロック'。 |  | Optional | {"minLength":0,"maxLength":32,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | ヘッダー |
| blockFileName | string | ブロックファイル名（入力） - ブロックのテンプレートファイル名 Fake観察文字長 6〜10; 観察値 'header', 'user_block'。 |  | Optional | {"minLength":0,"maxLength":32,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | header |


### Response

[Object: PUT /admin/block/block response](../schemas/put-admin-block-block.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| blockDeletable | boolean|null | ブロック削除可能フラグ - このブロックを管理画面から削除できるか。システム標準ブロックは削除不可 観察値 'false', 'true'。 | Required |  | false |
| blockName | string|null | ブロック名 - ブロックの表示名 Fake観察文字長 4〜8; 観察値 'ヘッダー', 'ユーザーブロック'。 | Required | {"minLength":0,"maxLength":32} | ヘッダー |
| blockId | string|null | ブロックID - dtb_block.id の不透明な文字列ハンドル。BeMart の BlockEntity 層は数値ではなく文字列として保持する。Fake 実装は `bk-` プレフィックス付きの英数字を生成し（シード `bk-header` を含む）、SQL 実装は dtb_block.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。非数値 ID は SqlBlockStorage では miss として扱われ getById / put / remove のいずれも 404 経路 (BlockUpdated / BlockDeleted) を踏むため、シードハンドル `bk-header` や `nonexistent-zzz` は Fake / SQL 双方で 404 が同形 Fake観察文字長 7〜9; 観察値 'bk-header', 'bk-user'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} | bk-header |
| blockFileName | string|null | ブロックファイル名 - ブロックのテンプレートファイル名 Fake観察文字長 6〜10; 観察値 'header', 'user_block'。 | Required | {"minLength":0,"maxLength":32} | header |

#### Links

| Relation | URL |
|----------|-----|
| goBlockList | [<code>page://self/admin/block/block-list</code>](/admin/block/block-list.md) |
## DELETE
ALPS `doDeleteBlock` に対応する DELETE 操作。

**ALPS**: `doDeleteBlock`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| blockId | string | ブロックID（入力） - dtb_block.id の不透明な文字列ハンドル。BeMart の BlockEntity 層は数値ではなく文字列として保持する。Fake 実装は `bk-` プレフィックス付きの英数字を生成し（シード `bk-header` を含む）、SQL 実装は dtb_block.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。非数値 ID は SqlBlockStorage では miss として扱われ getById / put / remove のいずれも 404 経路 (BlockUpdated / BlockDeleted) を踏むため、シードハンドル `bk-header` や `nonexistent-zzz` は Fake / SQL 双方で 404 が同形 Fake観察文字長 7〜9; 観察値 'bk-header', 'bk-user'。 |  | Required | {"minLength":0,"maxLength":128,"$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | bk-header |


### Response

[Object: DELETE /admin/block/block response](../schemas/delete-admin-block-block.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| message | string|null | 処理メッセージ - /admin/block/block のレスポンスに含まれる処理結果メッセージ。注文時お問い合わせ欄ではなく、画面遷移や完了表示のための通知文。 | Optional | {"minLength":0,"maxLength":32} | 配送は平日希望です。 |
| blockId | string|null | ブロックID - dtb_block.id の不透明な文字列ハンドル。BeMart の BlockEntity 層は数値ではなく文字列として保持する。Fake 実装は `bk-` プレフィックス付きの英数字を生成し（シード `bk-header` を含む）、SQL 実装は dtb_block.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。非数値 ID は SqlBlockStorage では miss として扱われ getById / put / remove のいずれも 404 経路 (BlockUpdated / BlockDeleted) を踏むため、シードハンドル `bk-header` や `nonexistent-zzz` は Fake / SQL 双方で 404 が同形 Fake観察文字長 7〜9; 観察値 'bk-header', 'bk-user'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} | bk-header |

#### Links

| Relation | URL |
|----------|-----|
| goBlockList | [<code>page://self/admin/block/block-list</code>](/admin/block/block-list.md) |
| goLayoutList | [<code>page://self/admin/layout/layout-list</code>](/admin/layout/layout-list.md) |