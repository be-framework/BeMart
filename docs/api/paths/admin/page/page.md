<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/page/page
EC-CUBE goPage + doUpdatePage + doDeletePage — single-row endpoint
(Wave 9 CMS).

Phase 3 — HTML FORM page. `onGet` exposes an {@see \AdminPageForm}
(Ray.WebFormModule AbstractForm) as `body['form']` pre-filled with the
persisted row, so the admin page editor (`Content/page_edit.twig`
port) can render real `<input>`s via `{{ form.input(...) }}`. The JSON
contexts (`app`, `prod`, `test`) ignore `body['form']`.




## GET
ALPS `goPage` に対応する GET 操作。

**ALPS**: `goPage` - ページ詳細を見る



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| pageId | string | ページID（入力） - dtb_page.id の不透明な文字列ハンドル。BeMart の PageEntity 層は数値ではなく文字列として保持する。Fake 実装は `pg-` プレフィックス付きの英数字を生成し（シード `pg-homepage` を含む）、SQL 実装は dtb_page.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。非数値 ID は SqlPageStorage では miss として扱われ getById / put / remove のいずれも 404 経路 (AdminPageFetched / PageUpdated / PageDeleted) を踏むため、シードハンドル `pg-homepage` や `nonexistent-zzz` は Fake / SQL 双方で 404 が同形 Fake観察文字長 6〜11; 観察値 'pg-homepage', 'pg-company', 'pg-foo'。 |  | Optional | {"minLength":0,"maxLength":128,"$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | pg-homepage |


### Response

[Object: GET /admin/page/page response](../schemas/get-admin-page-page.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| form | object|array|null | 入力フォーム - Aura/WebForm由来のフォームオブジェクト。フレームワーク内部構造のためschemaでは存在と型のみを契約する。 | Optional | {"$comment":"Aura/WebForm\u7531\u6765\u306e\u4e0d\u900f\u660e\u30d5\u30a9\u30fc\u30e0\u8868\u73fe\u3002Resource\u5883\u754c\u3067\u306f\u30d5\u30a9\u30fc\u30e0\u306e\u5b58\u5728\u3068\u30b3\u30f3\u30c6\u30ad\u30b9\u30c8\u3060\u3051\u3092\u5951\u7d04\u3057\u3001\u5185\u90e8\u69cb\u9020\u306f\u30d5\u30ec\u30fc\u30e0\u30ef\u30fc\u30af\u5883\u754c\u306b\u59d4\u306d\u308b\u305f\u3081\u8ffd\u52a0\u30ad\u30fc\u5236\u7d04\u3092\u7f6e\u304b\u306a\u3044\u3002"} |  |
| pageFileName | string|null | テンプレートファイル名 - ページのテンプレートファイル名 Fake観察文字長 3〜7; 観察値 'index', 'company', 'foo'。 | Required | {"minLength":0,"maxLength":32} | index |
| pageUrl | string|null | ページURL - ページのURLパス（Symfonyルート名。例: homepage, product_list） Fake観察文字長 3〜8; 観察値 'homepage', 'company', 'foo'。 | Required | {"format":"uri-reference","minLength":1,"maxLength":2048} | homepage |
| pageId | string|null | ページID - dtb_page.id の不透明な文字列ハンドル。BeMart の PageEntity 層は数値ではなく文字列として保持する。Fake 実装は `pg-` プレフィックス付きの英数字を生成し（シード `pg-homepage` を含む）、SQL 実装は dtb_page.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。非数値 ID は SqlPageStorage では miss として扱われ getById / put / remove のいずれも 404 経路 (AdminPageFetched / PageUpdated / PageDeleted) を踏むため、シードハンドル `pg-homepage` や `nonexistent-zzz` は Fake / SQL 双方で 404 が同形 Fake観察文字長 6〜11; 観察値 'pg-homepage', 'pg-company', 'pg-foo'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} | pg-homepage |
| pageName | string|null | ページ名 - 管理画面でのページ表示名 Fake観察文字長 3〜6; 観察値 'ホームページ', '会社案内', 'Foo'。 | Required | {"minLength":0,"maxLength":32} | ホームページ |
| csrfToken | string|null | 処理識別子 - フォーム送信の偽造を防ぐために送信元画面で発行されるトークン。Fake環境では deterministic な値を使う。 | Optional | {"minLength":8,"maxLength":160,"pattern":"^[A-Za-z0-9_.:-]+$"} | fake-csrf-token-bemart-2026 |
| pageEditType | int|null | ページ編集区分 - ページ編集レベル。0=EDIT_TYPE_USER（ユーザー作成、完全編集/削除可）, 1=EDIT_TYPE_PREVIEW（プレビュー）, 2=EDIT_TYPE_DEFAULT（システムページ、構造ロック・削除不可）, 3=EDIT_TYPE_DEFAULT_CONFIRM（内容編集可能なシステムページ、利用規約等）。editType>=2は削除不可 Fake観察数値 0〜2; 観察値 '0', '2'。 | Required | {"minimum":0} | 0 |

#### Links

| Relation | URL |
|----------|-----|
| goPageList | [<code>page://self/admin/page/page-list</code>](/admin/page/page-list.md) |
## PUT
ALPS `doUpdatePage` に対応する PUT 操作。

**ALPS**: `doUpdatePage` - ページを更新する



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| pageId | string | ページID（入力） - dtb_page.id の不透明な文字列ハンドル。BeMart の PageEntity 層は数値ではなく文字列として保持する。Fake 実装は `pg-` プレフィックス付きの英数字を生成し（シード `pg-homepage` を含む）、SQL 実装は dtb_page.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。非数値 ID は SqlPageStorage では miss として扱われ getById / put / remove のいずれも 404 経路 (AdminPageFetched / PageUpdated / PageDeleted) を踏むため、シードハンドル `pg-homepage` や `nonexistent-zzz` は Fake / SQL 双方で 404 が同形 Fake観察文字長 6〜11; 観察値 'pg-homepage', 'pg-company', 'pg-foo'。 |  | Required | {"minLength":0,"maxLength":128,"$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | pg-homepage |
| pageName | string | ページ名（入力） - 管理画面でのページ表示名 Fake観察文字長 3〜6; 観察値 'ホームページ', '会社案内', 'Foo'。 |  | Optional | {"minLength":0,"maxLength":32,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | ホームページ |
| pageUrl | string | ページURL（入力） - ページのURLパス（Symfonyルート名。例: homepage, product_list） Fake観察文字長 3〜8; 観察値 'homepage', 'company', 'foo'。 |  | Optional | {"minLength":0,"maxLength":2048,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | homepage |
| pageFileName | string | テンプレートファイル名（入力） - ページのテンプレートファイル名 Fake観察文字長 3〜7; 観察値 'index', 'company', 'foo'。 |  | Optional | {"minLength":0,"maxLength":32,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | index |


### Response

[Object: PUT /admin/page/page response](../schemas/put-admin-page-page.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| pageFileName | string|null | テンプレートファイル名 - ページのテンプレートファイル名 Fake観察文字長 3〜7; 観察値 'index', 'company', 'foo'。 | Required | {"minLength":0,"maxLength":32} | index |
| pageUrl | string|null | ページURL - ページのURLパス（Symfonyルート名。例: homepage, product_list） Fake観察文字長 3〜8; 観察値 'homepage', 'company', 'foo'。 | Required | {"format":"uri-reference","minLength":1,"maxLength":2048} | homepage |
| pageId | string|null | ページID - dtb_page.id の不透明な文字列ハンドル。BeMart の PageEntity 層は数値ではなく文字列として保持する。Fake 実装は `pg-` プレフィックス付きの英数字を生成し（シード `pg-homepage` を含む）、SQL 実装は dtb_page.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。非数値 ID は SqlPageStorage では miss として扱われ getById / put / remove のいずれも 404 経路 (AdminPageFetched / PageUpdated / PageDeleted) を踏むため、シードハンドル `pg-homepage` や `nonexistent-zzz` は Fake / SQL 双方で 404 が同形 Fake観察文字長 6〜11; 観察値 'pg-homepage', 'pg-company', 'pg-foo'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} | pg-homepage |
| pageName | string|null | ページ名 - 管理画面でのページ表示名 Fake観察文字長 3〜6; 観察値 'ホームページ', '会社案内', 'Foo'。 | Required | {"minLength":0,"maxLength":32} | ホームページ |
| pageEditType | int|null | ページ編集区分 - ページ編集レベル。0=EDIT_TYPE_USER（ユーザー作成、完全編集/削除可）, 1=EDIT_TYPE_PREVIEW（プレビュー）, 2=EDIT_TYPE_DEFAULT（システムページ、構造ロック・削除不可）, 3=EDIT_TYPE_DEFAULT_CONFIRM（内容編集可能なシステムページ、利用規約等）。editType>=2は削除不可 Fake観察数値 0〜2; 観察値 '0', '2'。 | Required | {"minimum":0} | 0 |

#### Links

| Relation | URL |
|----------|-----|
| goPage | [<code>page://self/admin/page/page</code>](/admin/page/page.md) |
## DELETE
ALPS `doDeletePage` に対応する DELETE 操作。

**ALPS**: `doDeletePage` - ページを削除する



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| pageId | string | ページID（入力） - dtb_page.id の不透明な文字列ハンドル。BeMart の PageEntity 層は数値ではなく文字列として保持する。Fake 実装は `pg-` プレフィックス付きの英数字を生成し（シード `pg-homepage` を含む）、SQL 実装は dtb_page.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。非数値 ID は SqlPageStorage では miss として扱われ getById / put / remove のいずれも 404 経路 (AdminPageFetched / PageUpdated / PageDeleted) を踏むため、シードハンドル `pg-homepage` や `nonexistent-zzz` は Fake / SQL 双方で 404 が同形 Fake観察文字長 6〜11; 観察値 'pg-homepage', 'pg-company', 'pg-foo'。 |  | Required | {"minLength":0,"maxLength":128,"$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | pg-homepage |


### Response

[Object: DELETE /admin/page/page response](../schemas/delete-admin-page-page.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| message | string|null | 処理メッセージ - /admin/page/page のレスポンスに含まれる処理結果メッセージ。注文時お問い合わせ欄ではなく、画面遷移や完了表示のための通知文。 | Optional | {"minLength":0,"maxLength":32} | 配送は平日希望です。 |
| pageId | string|null | ページID - dtb_page.id の不透明な文字列ハンドル。BeMart の PageEntity 層は数値ではなく文字列として保持する。Fake 実装は `pg-` プレフィックス付きの英数字を生成し（シード `pg-homepage` を含む）、SQL 実装は dtb_page.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。非数値 ID は SqlPageStorage では miss として扱われ getById / put / remove のいずれも 404 経路 (AdminPageFetched / PageUpdated / PageDeleted) を踏むため、シードハンドル `pg-homepage` や `nonexistent-zzz` は Fake / SQL 双方で 404 が同形 Fake観察文字長 6〜11; 観察値 'pg-homepage', 'pg-company', 'pg-foo'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} | pg-homepage |

#### Links

| Relation | URL |
|----------|-----|
| goPageList | [<code>page://self/admin/page/page-list</code>](/admin/page/page-list.md) |
| goBlockList | [<code>page://self/admin/block/block-list</code>](/admin/block/block-list.md) |